<?php

class Village_group_goodsModel extends Model
{
    //超值热卖(每次随机取二十个商品，判断这二十个商品的现价与原价之差，越大越靠前，刷新或重新进入重新取二十个商品再进行判断)
    public function getHotRecom($leader_id = 0, $limit = 20){
        $goods = $this->get_goods($leader_id, 0, 0, 1, 100, ['goods.is_new_user' => 0]);
        $array = [];
        $count = count($goods);
        if($count > $limit){
            $rand = array_rand($goods, $limit);
            foreach ($rand as $value) {
                $array[] = $goods[$value];
            }
        }
        else{
            $array = $goods;
        }
        foreach ($array as $key => $value) {
            $array[$key]['minus'] = 0;
            if($value['old_price'] > $value['price']){
                $array[$key]['minus'] = sprintf('%.2f',($value['old_price'] - $value['price']));
            }
        }
        array_multisort(array_column($array, 'minus'),SORT_DESC,$array);
        return $array;
    }

    //获取商品
    //$show_overdue = 是否展示过期
    public function get_goods($leader_id = 0, $cate_id = 0, $activity_id = 0, $page = 1, $pagesize = 20, $extra_where = [], $show_overdue = false, $is_list = true){
        $where = [];
        if(!$show_overdue){
            $where['goods.status'] = ['in', [1, 2]];
        }
        $where['goods.start_time'] = ['lt', time() + 7*86400];//展示一周内到未来的商品
        if(!empty($extra_where)){
            $where = array_merge($where, $extra_where);
        }
        if($cate_id){
            $where['goods.cat_fid'] = $cate_id;
        }
        if($leader_id){
            $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
            $where['_string'] = "(b.city_id IS NUll OR b.city_id = 0 OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND FIND_IN_SET({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND !FIND_IN_SET({$address['id']} , b.address_list)))";
        }
        if(!$show_overdue){
            $_endtime = "(goods.end_time = 0 OR goods.end_time > ".time().")";
        }
        else{
            $_endtime = "1";
        }
        $where['_string'] = $where['_string'] ? $where['_string']." AND ".$_endtime : $_endtime;
        $activity_goods_price = [];//限时活动商品价格
        $activity_goods_buylimit = [];//限购活动的限购数
        $check_activity = true;
        $Village_group_activity = D("Village_group_activity");
        $Village_group_activity_goods = D("Village_group_activity_goods");
        if($activity_id){
            $activity_info = $Village_group_activity->where(['activity_id'=>$activity_id])->find();
            $activity_goods = $Village_group_activity_goods->where(['activity_id'=>$activity_id,'is_del'=>0])->field("goods_id,buy_price")->select();
            if(empty($activity_goods)) return [];
            if($activity_info['type'] == 2){
                $check_activity = false;
                $activity_goods_price = array_column($activity_goods, 'buy_price', 'goods_id');
            }
            $where['goods.goods_id'] = ['in', array_column($activity_goods, 'goods_id')];
        }
        if($check_activity){
            //找最近的限时抢购活动的活动价(已经开始了的)
            $near_where = [
                'activity.status' => 1,
                'activity.type' => 2,
                'activity.start_time' => ['between',[strtotime(date('Y-m-d 00:00:00')),time()]],//今天的已经开始了的限时抢购
                'actgoods.is_del' => 0
            ];
            $last_activity = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->where($near_where)->field("actgoods.goods_id,actgoods.buy_limit,actgoods.buy_price,actgoods.after_activity,activity.start_time,activity.end_time")->order("activity.start_time asc")->select();
            if($last_activity){
                foreach ($last_activity as $key => $value) {
                    if(time() > $value['start_time'] && time() < $value['end_time']){
                        $activity_goods_price[$value['goods_id']] = $value['buy_price'];
                        $activity_goods_buylimit[$value['goods_id']] = $value['buy_limit'];
                    }
                    elseif($value['after_activity'] == '2'){//活动结束后，依然按照限时价购买 
                        $activity_goods_price[$value['goods_id']] = $value['buy_price'];
                    }
                }
            }
        }
        $field = 'goods.*,i.increase_price,b.is_add_price,b.min_price,b.max_price,b.price as area_price';

        $left_join = 'as goods LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON goods.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = goods.goods_id';
        if($leader_id){
            $left_join .= ' AND i.header_uid='.$leader_id;
        }
        
        if($page != -1){
            $limit = (($page-1)*$pagesize).','.$pagesize;
            $goods = $this->field($field)->join($left_join)->where($where)->limit($limit)->order('`goods`.`status` ASC,`goods`.`sort` DESC,`goods`.`goods_id` DESC')->group("goods.goods_id")->select();
        }
        else{
            $goods = $this->field($field)->join($left_join)->where($where)->order('`goods`.`status` ASC,`goods`.`sort` DESC,`goods`.`goods_id` DESC')->group("goods.goods_id")->select();
        }

        //调取商品参与的每日必买活动
        $goodsids = array_column($goods, 'goods_id');
        $labelids = array_column($goods, 'label');
        $getActivityInfo = D("Village_group_activity_goods")->getActivityInfo($goodsids, 1);
        $goods_activity = array_column($getActivityInfo, 'activity_id', 'goods_id');
        $activity_goods_daybuy = array_column($getActivityInfo, 'buy_limit', 'goods_id');

        //调取商品的标签
        $labelids_arr = ['status'=>1];
        if($labelids){
            $labelids_arr['id'] = ['in',$labelids];
        }
        $labels = M("Village_group_label")->where($labelids_arr)->select();
        $labels_arr = [];
        foreach ($labels as $key => $value) {
            $value['image'] = replace_file_domain($value['image']);
            $labels_arr[$value['id']] = $value;
        }

        $Village_group_activity_goods = D("Village_group_activity_goods");
        //正在进行的限购活动
        $ing_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            'activity.start_time' => ['lt',time()],
            'activity.end_time' => ['gt',time()],
            'actgoods.is_del' => 0
        ];
        $ing_activity = array_column($Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->join("left join ".C("DB_PREFIX")."village_group_goods as g on actgoods.goods_id=g.goods_id")->where($ing_where)->field("actgoods.goods_id")->select(), 'goods_id');

        //限时抢购开始之前，不能购买
        $near_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            'activity.start_time' => ['between',[time(), strtotime(date('Y-m-d 23:59:59'))]],//今天未开始的限时抢购
            'actgoods.is_del' => 0
        ];
        if($ing_activity){
            $near_where['actgoods.goods_id'] = ['not in' , $ing_activity];
        }
        $last_activity = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->join("left join ".C("DB_PREFIX")."village_group_goods as g on actgoods.goods_id=g.goods_id")->where($near_where)->field("actgoods.goods_id,g.name,actgoods.goods_id,actgoods.buy_price,actgoods.after_activity,actgoods.before_activity")->order("activity.start_time asc")->select();
        $last_goods = [];
        if($last_activity){
            foreach ($last_activity as $key => $value) {
                if($value['before_activity'] == '1' && !in_array($value['goods_id'], $last_goods)){//活动开始前，不能购买 
                    $last_goods[] = $value['goods_id'];
                }
            }
        }

        if($goods){
            foreach ($goods as $key => $value) {
                if($is_list){
                    unset($goods[$key]['content']);
                }
                $goods[$key]['buy_type'] = '0';
                if(C("config.village_group_express_open") == 1){
                    $goods[$key]['buy_type'] = '1';
                    if(in_array($value['goods_id'],$ing_activity)){
                        $goods[$key]['buy_type'] = '2';
                    }
                    elseif(isset($goods_activity[$value['goods_id']])){
                        $goods[$key]['buy_type'] = '2';
                    }
                }
                //实际价格：'城市价' 优先于 '活动价' 优先于 '本价'   （+团长加价）
                $goods[$key]['increase_price'] = $value['is_add_price'] == '1' && $value['increase_price'] ? $value['increase_price'] : 0;
                if($value['area_price']){
                    $goods[$key]['_price_old'] = $value['area_price'];
                    $goods[$key]['price'] = $value['area_price'] + $goods[$key]['increase_price'];    
                }
                elseif($activity_goods_price[$value['goods_id']]){
                    $goods[$key]['_price_old'] = $activity_goods_price[$value['goods_id']];
                    $goods[$key]['price'] = $activity_goods_price[$value['goods_id']] + $goods[$key]['increase_price'];       
                }
                else{
                    $goods[$key]['_price_old'] = $value['price'];
                    $goods[$key]['price'] = $value['price'] + $goods[$key]['increase_price']; 
                }
                $goods[$key]['status'] = $value['status']==4?2:($value['end_time'] > 0 && $value['end_time'] < time() ? 2 : 1);//2=已下架（已过期） 1=正常
                $goods[$key]['limit_buy'] = isset($activity_goods_buylimit[$value['goods_id']]) ? $activity_goods_buylimit[$value['goods_id']] : ($activity_goods_daybuy[$value['goods_id']] ? $activity_goods_daybuy[$value['goods_id']] : ($value['is_new_user'] == 1 ? $value['new_user_limit_buy'] : 0));//活动限购数 0=不限购
                $goods[$key]['is_no_start'] = $value['start_time'] > time() ? 1 : 0;//是否已经开始
                $goods[$key]['surplus'] = $value['stock_num'] == -1 ? "无限库存" : ($value['stock_num']-$value['sell_count']-($value['locking_num']<0?0:$value['locking_num']) < 0 ? 0 : $value['stock_num']-$value['sell_count']-($value['locking_num']<0?0:$value['locking_num']));
                $goods[$key]['sell_count'] = $value['sell_count'] + $value['virtual_sell_count'];
                $goods[$key]['stock_status'] = $value['stock_num'] <= $value['sell_count'] || $value['status'] == 2 ? 1 : 0;//商品库存状态 1=已售罄 0=正常
                $goods[$key]['cutting_status'] = 0;
                if($value['arrive_type'] == 5 && $value['arrive_cutting_datetime'] > 0 && time() > $value['arrive_cutting_datetime']){
                    $goods[$key]['cutting_status'] = 1;
                }
                $goods[$key]['limit_buy_status'] = in_array($value['goods_id'], $last_goods) ? 1 : 0;//限时抢购之前，不能购买 1=不能购买 0=可以购买
                $goods[$key]['image'] = $is_list ? thumb_img(replace_file_domain($value['image']), 240, 240, 'fill') : replace_file_domain($value['image']);
                $goods[$key]['labels'] = [];
                if($goods_activity[$value['goods_id']]){//优先展示每日必买标签
                    $goods[$key]['labels'] = [
                        'name' => '必买',
                        'color' => '',
                        'color2' => '',
                        'type' => 0,
                    ];
                }
                elseif($labels_arr[$value['label']]){//如果没有每日必买活动，那就展示标签
                    $goods[$key]['labels'] = $labels_arr[$value['label']];
                }
                //消费一元获得积分
                $buy_can_get_score = PD('Goods_score')->getGoodsScore($goods[$key]['price'],'village_group',false,$goods[$key]['score_percent']);
                $goods[$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $goods[$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
            return array_values($goods);
        }
        else{
            return [];
        }
    }
    
    /**
     * 检查商品
     * 商品下架、过期、库存不足、参与活动，都有可能不能通过此检查
     * @param  [type]  $leader_id [description]
     * @param  [type]  $goods     商品ID与对应数量的映射
     * @return 
     */
    public function check_goods($leader_id, $goods, $uid = 0, $check_new_user = true, $order_id = 0){
        if(empty($leader_id) || empty($goods)){
            return ['status' => false, 'msg' => '参数错误'];
        }
        $Village_group_activity_goods = D("Village_group_activity_goods");

        //正在进行的限购活动
        $ing_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            'activity.start_time' => ['lt',time()],
            'activity.end_time' => ['gt',time()],
            'actgoods.is_del' => 0
        ];
        $ing_activity = array_column($Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->join("left join ".C("DB_PREFIX")."village_group_goods as g on actgoods.goods_id=g.goods_id")->where($ing_where)->field("actgoods.goods_id")->select(), 'goods_id');

        //限时抢购开始之前，不能购买
        $near_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            'activity.start_time' => ['between',[time(), strtotime(date('Y-m-d 23:59:59'))]],//今天未开始的限时抢购
            'actgoods.is_del' => 0
        ];
        if($ing_activity){
            $near_where['actgoods.goods_id'] = ['not in' , $ing_activity];
        }
        $last_activity = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->join("left join ".C("DB_PREFIX")."village_group_goods as g on actgoods.goods_id=g.goods_id")->where($near_where)->field("actgoods.goods_id,g.name,actgoods.goods_id,actgoods.buy_price,actgoods.before_activity,actgoods.after_activity")->order("activity.start_time asc")->select();
        if($last_activity){
            foreach ($last_activity as $key => $value) {
                if($value['before_activity'] == '1' && $goods[$value['goods_id']]){//活动开始前，不能购买 
                    return ['status' => false, 'msg' => $value['name']."已参与活动，活动开始前不能购买"];
                }
            }
        }

        

        //限时抢购价格
        $activity_goods_price = [];//限时活动商品价格
        $near_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            'activity.start_time' => ['between',[strtotime(date('Y-m-d 00:00:00')),time()]],//今天的已经开始了的限时抢购
            'actgoods.is_del' => 0
        ];
        $last_activity = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->where($near_where)->field("actgoods.goods_id,actgoods.buy_price,actgoods.after_activity,activity.start_time,activity.end_time")->order("activity.start_time asc")->select();
        if($last_activity){
            foreach ($last_activity as $key => $value) {
                if(time() > $value['start_time'] && time() < $value['end_time']){
                    $activity_goods_price[$value['goods_id']] = $value['buy_price'];
                }
                elseif($value['after_activity'] == '2'){//活动结束后，依然按照限时价购买 
                    $activity_goods_price[$value['goods_id']] = $value['buy_price'];
                }
            }
        }

        $goods_ids = array_keys($goods);
        //限购数量
        $limit_buy_where = [
            'activity.status' => 1,
            'activity.type' => 2,
            '_string' => 'activity.start_time < '.time().' and activity.end_time > '.time(),//正在进行的限购活动
            'actgoods.is_del' => 0,
            'actgoods.goods_id' => ['in', $goods_ids]
        ];
        $limit_buy_goods = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->where($limit_buy_where)->field("actgoods.goods_id,actgoods.buy_limit,activity.activity_id,activity.start_time,activity.end_time")->select();

        if(!empty($limit_buy_goods)){
            //已经购买过的数量
            $limit_buy_where = [
                'o.status' => ['in', [0,1,2,3,5,6,7,8,13]],
                'o.uid' => $uid,
                'd.goods_id' => ['in', array_unique(array_column($limit_buy_goods, 'goods_id'))],
                'o.add_time' => ['between', [$limit_buy_goods[0]['start_time'], $limit_buy_goods[0]['end_time']]],//正在进行的限购活动
            ];
            if($order_id > 0){
                $limit_buy_where['o.order_id'] = ['neq',$order_id];
            }
            $limit_buy_field = "sum(d.num) as sm, d.goods_id";
            $limit_buy_already = M("Village_group_order_detail")->alias("d")->join("left join ".C("DB_PREFIX")."village_group_order as o on o.order_id=d.order_id")->field($limit_buy_field)->where($limit_buy_where)->group("d.goods_id")->select();
            $limit_buy_already_arr = array_column($limit_buy_already, 'sm', 'goods_id');

            foreach ($limit_buy_goods as $key => $value) {
                $already = isset($limit_buy_already_arr[$value['goods_id']]) ? $limit_buy_already_arr[$value['goods_id']] : 0;
                if($value['buy_limit'] != 0 && $value['buy_limit'] < ($goods[$value['goods_id']]+$already)){
                    return ['status' => false, 'msg' => "您购买的数量超出了活动限购数量，不能购买"];
                }
            }
        }

        $daybuy_where = [
            'activity.status' => 1,
            'activity.type' => 1,
            '_string' => 'activity.start_time < '.time().' and activity.end_time > '.time(),//正在进行的每日必买
            'actgoods.is_del' => 0,
            'actgoods.goods_id' => ['in', $goods_ids]
        ];
        $daybuy_goods = $Village_group_activity_goods->alias("actgoods")->join("left join ".C("DB_PREFIX")."village_group_activity as activity on activity.activity_id=actgoods.activity_id")->where($daybuy_where)->field("actgoods.goods_id,actgoods.buy_limit,activity.activity_id,activity.start_time,activity.end_time")->select();
        if(!empty($daybuy_goods)){
            //已经购买过的数量
            $daybuy_where = [
                'o.status' => ['in', [0,1,2,3,5,6,7,8,13]],
                'o.uid' => $uid,
                'd.goods_id' => ['in', array_unique(array_column($daybuy_goods, 'goods_id'))],
                'o.add_time' => ['between', [$daybuy_goods[0]['start_time'], $daybuy_goods[0]['end_time']]],//正在进行的限购活动
            ];
            if($order_id > 0){
                $daybuy_where['o.order_id'] = ['neq',$order_id];
            }
            $daybuy_field = "sum(d.num) as sm, d.goods_id";
            $daybuy_already = M("Village_group_order_detail")->alias("d")->join("left join ".C("DB_PREFIX")."village_group_order as o on o.order_id=d.order_id")->field($daybuy_field)->where($daybuy_where)->group("d.goods_id")->select();
            $daybuy_already_arr = array_column($daybuy_already, 'sm', 'goods_id');

            foreach ($daybuy_goods as $key => $value) {
                $already = isset($daybuy_already_arr[$value['goods_id']]) ? $daybuy_already_arr[$value['goods_id']] : 0;
                if($value['buy_limit'] != 0 && $value['buy_limit'] < ($goods[$value['goods_id']]+$already)){
                    return ['status' => false, 'msg' => "您购买的数量超出了每日必买活动限购数量，不能购买"];
                }
            }
        }
        


        $where = [];
        $where['goods.goods_id'] = ['in', $goods_ids];
        $where['goods.status'] = 1;
        $where['goods.start_time'] = ['elt', time()];
        $address = M('Village_group_pick_address')->where(array('header_uid'=>$leader_id))->find();
        $where['_string'] = "(b.city_id IS NUll OR b.city_id = 0 OR (ga.area_id = {$address['province_id']}) OR ga.area_id = {$address['city_id']} OR ga.area_id = {$address['area_id']}) AND (b.buy_user_type IS NUll OR b.buy_user_type=0 OR (b.buy_user_type=1 AND FIND_IN_SET({$address['id']} , b.address_list)) OR  (b.buy_user_type=2 AND !FIND_IN_SET({$address['id']} , b.address_list)))";
        $_endtime = "(goods.end_time = 0 OR goods.end_time > ".time().")";
        $where['_string'] = $where['_string']." AND ".$_endtime;
        $field = 'goods.*,i.increase_price,b.is_add_price,b.min_price,b.max_price,b.price as area_price';
        $left_join = 'as goods LEFT JOIN '.C('DB_PREFIX').'village_group_goods_bind_area b ON goods.goods_id = b.goods_id LEFT JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id  LEFT JOIN '.C('DB_PREFIX').'area a ON a.area_id = ga.area_id LEFT JOIN '.C('DB_PREFIX').'village_group_goods_increase_price i ON i.goods_id = goods.goods_id';
        if($leader_id){
            $left_join .= ' AND i.header_uid='.$leader_id;
        }
        $data = $this->field($field)->join($left_join)->where($where)->select();

        if(empty($data)){
            return ['status' => false, 'msg' => "该商品已下架"];
        }
        $now = array_column($data, 'goods_id');
        $diff = array_diff($goods_ids, $now);
        if(!empty($diff)){
            $diff_info = M("Village_group_goods")->where(['goods_id'=>['in', $diff]])->field('goods_id, name')->select();
            return ['status' => false, 'msg' => implode(',',array_column($diff_info, 'name'))."已下架"];   
        }
        $return = [
            'status' => true,
            'data' => []
        ];
        foreach ($data as $key => $value) {
            if($value['is_new_user'] == '1' && $check_new_user){
                $w = [
                    'o.status' => ['in', [1,2,3,5,6,7,8,13]],
                    'o.uid' => $uid
                ];
                $newbuy = M("Village_group_order_detail")->alias("d")->join("left join ".C("DB_PREFIX")."village_group_order as o on o.order_id=d.order_id")->where($w)->find();
                if($newbuy){
                    return ['status' => false, 'msg' => $value['name']."仅限新用户购买，您可以逛逛其他商品哦"];  
                }
                if($value['new_user_limit_buy'] > 0 && $value['new_user_limit_buy'] < $goods[$value['goods_id']]){
                    return ['status' => false, 'msg' => "您购买的数量超出了新人专享商品限购数，可以逛逛其他商品哦"];  
                }
            }
            if($value['arrive_type'] == 5 && $check_new_user){//固定日期
                if(time() > $value['arrive_cutting_datetime']){
                    return ['status' => false, 'msg' => $value['name']."已过截单时间，不能购买"];  
                }
            }
            if($goods[$value['goods_id']] > $value['stock_num'] - $value['sell_count'] - ($value['locking_num']??0)){
                return ['status' => false, 'msg' => $value['name']."库存不足，不能购买"];
            }
            $value['sell_count'] = $value['sell_count'] + $value['virtual_sell_count'];
            if($value['arrive_days'] == 5 && $value['arrive_cutting_datetime'] > 0 && time() > $value['arrive_cutting_datetime'] && $check_new_user){
                return ['status' => false, 'msg' => $value['name']."已过截单时间，不能购买"];   
            }
            //实际价格：'城市价' 优先于 '活动价' 优先于 '本价'   （+团长加价）
            $value['increase_price'] = $value['is_add_price'] == '1' && $value['increase_price'] ? $value['increase_price'] : 0;
            if($value['area_price']){
                $data[$key]['price'] = $value['area_price'] + $value['increase_price'];    
            }
            elseif($activity_goods_price[$value['goods_id']]){
                $goods[$key]['price'] = $activity_goods_price[$value['goods_id']] + $value['increase_price'];       
            }
            else{
                $data[$key]['price'] = $value['price'] + $value['increase_price']; 
            }
            $return['data'][$value['goods_id']] = $data[$key]['price'];
        }
        return $return;
    }

     // 获取对应拼团商品的最近一期的配送时间  每日不用处理   每周 传值 1-7   每月传值1-30  周期数 2 即 多加一天或者多加一周或者多加一个月
    public function lately_cycle_time($goods_id, $time=0, $is_time_stamp=false, $periods=1,$now_time=0, $first_time=0){
        if (!$goods_id) {
            return 0;
        }
        $periods = $periods>1?($periods-1):0;
        $cycle_info = M('Village_group_cycle')->where(array('goods_id'=>$goods_id))->find();
        if (empty($cycle_info)) {
            return 0;
        }
        if ($cycle_info['delivery_date']) {
            $cycle_info['delivery_date_arr'] = explode(',',$cycle_info['delivery_date']);
        }
        if ($now_time<=0) {
            $now_time = strtotime(date('Y-m-d'));
        }
        $limit_days = $cycle_info['limit_days']>0?intval($cycle_info['limit_days']):0;
        if ($cycle_info['limit_time'] && $periods==0) {
            $limit_time_str = strtotime(date('Y-m-d') . $cycle_info['limit_time']);
            if (time() > $limit_time_str) {
                $limit_days += 1;
            }
        }
        // 根据周期类型 处理时间问题
        $choose_date=$this->choose_date;
        $lately_time = strtotime("+".$limit_days." day", $now_time);
        if ($cycle_info['cycle_type'] == 1) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            }
            // 每日送
            if (!$is_time_stamp) {
                $lately_time = date('Y-m-d',$lately_time);
                if ($limit_days == 0) {
                    $lately_time .= '(当天)';
                }
            }
        } elseif ($cycle_info['cycle_type'] == 2) {
            if ($periods>0) {
                $week_periods = $periods * 7;
                $lately_time = strtotime("+".$week_periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            }
            // 每周送
            $week = $choose_date['week'];
            $w = date("w", $lately_time);
            if ($w == 0) {
                $w = 7;
            }
            if ($time >0 && $time<=7 && $time > $w) {
                $time_days = $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            } elseif ($time >0 && $time<=7 && $w > $time) {
                $time_days = 7 + $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            }
            if ($cycle_info['is_all_date']==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif ($cycle_info['delivery_date_arr'] && in_array($w, $cycle_info['delivery_date_arr'])) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif (empty($cycle_info['delivery_date_arr'])) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
        } elseif ($cycle_info['cycle_type'] == 3) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." month", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            }
            // 每月送
            $day = intval(date("d", $lately_time));
            // 计算当前月份第一天
            $first_day = date("Y-m-01", $lately_time);
            // 计算当前月份最后一天
            $last_time = strtotime("$first_day +1 month -1 day");
            $last_day = date('d', $last_time);
            if ($time>$last_day) {
                // 不满足30天的按照最大天数算 比如2月份
                $time = $last_day;
            }
            if ($time >0 && $time<=30 && $time > $day) {
                $time_days = $time - $day;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $day = $time;
            } elseif ($time >0 && $time<=30 && $day > $time) {
                // 这里需要计算下一个月的所选日期
                $time_days = $day - $time;
                $lately_time = strtotime("+1 month -".$time_days." day", $lately_time);
                $day = $time;
            }

            $to_day = date('Y-m-d');
            if ($cycle_info['is_all_date']==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif ($cycle_info['delivery_date_arr'] && in_array($day, $cycle_info['delivery_date_arr'])) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif (empty($cycle_info['delivery_date_arr'])) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
            if ($lately_time==$to_day) {
                $lately_time .= '(当天)';
            }
        }

        return $lately_time;
    }
    // 获取对应拼团商品的最近一期的配送时间  每日不用处理   每周 传值 1-7   每月传值1-30  周期数 2 即 多加一天或者多加一周或者多加一个月
    public function order_lately_cycle_time($order_info = array(), $goods_id,$time=0, $is_time_stamp=false, $periods=1,$now_time=0, $first_time=0){
        if (!$goods_id) {
            return 0;
        }
        $periods = $periods>1?($periods-1):0;
        if (empty($order_info)) {
            return 0;
        }
        $cycle_info = M('Village_group_cycle')->where(array('goods_id'=>$goods_id))->find();
        if ($order_info['delivery_date_info']) {
            $delivery_date = $order_info['delivery_date_info'];
        } else {
            $delivery_date = $cycle_info['delivery_date'];
        }
        if ($order_info['is_all_date']) {
            $is_all_date = $order_info['is_all_date'];
        } else {
            $is_all_date = $cycle_info['is_all_date'];
        }
        if ($order_info['limit_days'] != '') {
            $limit_days = $order_info['limit_days'];
        } else {
            $limit_days = $cycle_info['limit_days'];
        }
        if ($order_info['limit_time'] != '') {
            $limit_time = $order_info['limit_time'];
        } else {
            $limit_time = $cycle_info['limit_time'];
        }
        $delivery_date_arr = array();
        if ($delivery_date) {
            $delivery_date_arr = explode(',',$delivery_date);
        }
        if ($now_time<=0) {
            $now_time = strtotime(date('Y-m-d'));
        }
        $limit_days = $limit_days>0?intval($limit_days):0;
        if ($order_info['limit_time'] && $periods==0) {
            $limit_time_str = strtotime(date('Y-m-d') . $limit_time);
            if (time() > $limit_time_str) {
                $limit_days += 1;
            }
        }
        // 根据周期类型 处理时间问题
        $choose_date=$this->choose_date;
        $lately_time = strtotime("+".$limit_days." day", $now_time);
        if ($order_info['cycle_type'] == 1) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 day", $lately_time);
                }
            }
            // 每日送
            if (!$is_time_stamp) {
                $lately_time = date('Y-m-d',$lately_time);
                if ($limit_days == 0) {
                    $lately_time .= '(当天)';
                }
            }
        } elseif ($order_info['cycle_type'] == 2) {
            if ($periods>0) {
                $week_periods = $periods * 7;
                $lately_time = strtotime("+".$week_periods." day", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+7 day", $lately_time);
                }
            }
            // 每周送
            $week = $choose_date['week'];
            $w = date("w", $lately_time);
            if ($w == 0) {
                $w = 7;
            }
            if ($time >0 && $time<=7 && $time > $w) {
                $time_days = $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            } elseif ($time >0 && $time<=7 && $w > $time) {
                $time_days = 7 + $time - $w;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $w = $time;
            }
            if ($is_all_date==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif ($delivery_date_arr && in_array($w, $delivery_date_arr)) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } elseif (empty($delivery_date_arr)) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                    $lately_time .= '('.$week[$w].')';
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
        } elseif ($order_info['cycle_type'] == 3) {
            if ($periods>0) {
                $lately_time = strtotime("+".$periods." month", $lately_time);
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            } else {
                if ($first_time>0 && $lately_time<=$first_time) {
                    $lately_time = strtotime("+1 month", $lately_time);
                }
            }
            // 每月送
            $day = intval(date("d", $lately_time));
            // 计算当前月份第一天
            $first_day = date("Y-m-01", $lately_time);
            // 计算当前月份最后一天
            $last_time = strtotime("$first_day +1 month -1 day");
            $last_day = date('d', $last_time);
            if ($time>$last_day) {
                // 不满足30天的按照最大天数算 比如2月份
                $time = $last_day;
            }
            if ($time >0 && $time<=30 && $time > $day) {
                $time_days = $time - $day;
                $lately_time = strtotime("+".$time_days." day", $lately_time);
                $day = $time;
            } elseif ($time >0 && $time<=30 && $day > $time) {
                // 这里需要计算下一个月的所选日期
                $time_days = $day - $time;
                $lately_time = strtotime("+1 month -".$time_days." day", $lately_time);
                $day = $time;
            }

            $to_day = date('Y-m-d');
            if ($is_all_date==1) {
                // 如果在后台设置当前时间全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif ($delivery_date_arr && in_array($day, $delivery_date_arr)) {
                // 如果在后台设置的存在
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } elseif (empty($delivery_date_arr)) {
                // 如果后台未选择任意一个 则默认全选
                if (!$is_time_stamp) {
                    $lately_time = date('Y-m-d',$lately_time);
                }
            } else {
                // 选择时间不符合范围，提示刷新
                return -1;
            }
            if ($lately_time==$to_day) {
                $lately_time .= '(当天)';
            }
        }

        return $lately_time;
    }

    // 相关参数
    public $choose_date = array(
        'week' => array(
            '1' => '周一',
            '2' => '周二',
            '3' => '周三',
            '4' => '周四',
            '5' => '周五',
            '6' => '周六',
            '7' => '周日',
        ),
        'date' => array(
            '1' => 1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,
            '11'=>11,'12'=>12,'13'=>13,'14'=>14,'15'=>15,'16'=>16,'17'=>17,'18'=>18,'19'=>19,'20'=>20,
            '21'=>21,'22'=>22,'23'=>23,'24'=>24,'25'=>25,'26'=>26,'27'=>27,'28'=>28,'29'=>29,'30'=>30
        ),
        'limit_time' => array(
            '0' => '00:00','1'=>'01:00','2'=>'02:00','3'=>'03:00','4'=>'04:00','5'=>'05:00',
            '6'=>'06:00','7' => '07:00','8'=>'08:00','9'=>'09:00','10'=>'10:00','11'=>'11:00',
            '12'=>'12:00','13'=>'13:00','14' => '14:00','15'=>'15:00','16'=>'16:00','17'=>'17:00',
            '18'=>'18:00','19'=>'19:00','20'=>'20:00','21' => '21:00','22'=>'22:00','23'=>'23:00'
        ),
        'date_msg' => array(
            '1' => '1号','2'=>'2号','3'=>'3号','4'=>'4号','5'=>'5号','6'=>'6号','7'=>'7号','8'=>'8号','9'=>'9号','10'=>'10号',
            '11'=>'11号','12'=>'12号','13'=>'13号','14'=>'14号','15'=>'15号','16'=>'16号','17'=>'17号','18'=>'18号','19'=>'19号','20'=>'20号',
            '21'=>'21号','22'=>'22号','23'=>'23号','24'=>'24号','25'=>'25号','26'=>'26号','27'=>'27号','28'=>'28号','29'=>'29号','30'=>'30号'
        )
    );
    
    /**
     * 更新社区团购商品库存锁定数量
     * $updateStockAry 更新商品库存数组
     *          {
     *              'goods_id'=>$kNum,      //商品id
     *              'lock_stock_num'=>$vNum,//库存锁定变更数量
     *              'lock_stock_type'=>0    //库存锁定数量变更类型（0：减少，1：增加）
     *          }
     */
    public function updateStock($updateStockAry=[])
    {
        if(!$updateStockAry || !is_array($updateStockAry)){
            return false;
        }
        $sql = 'UPDATE '.C('DB_PREFIX').'village_group_goods SET locking_num = CASE goods_id';
        foreach ($updateStockAry as $valueStock){
            if(!isset($valueStock['goods_id']) || empty($valueStock['goods_id']) || !isset($valueStock['lock_stock_num']) || !isset($valueStock['lock_stock_type'])){
                return false;
            }
            if($valueStock['lock_stock_num']==0){//变更数量都为0无需修改
                return true;
            }
            if($valueStock['lock_stock_num'] > 0){
                $sql .= ' WHEN \''.$valueStock['goods_id'].'\' THEN `locking_num`'.($valueStock['lock_stock_type']?'+':'-').$valueStock['lock_stock_num'];
            }
        }
        $sql .= ' END WHERE goods_id IN ('.implode(',',array_column($updateStockAry,'goods_id')).')';
        M()->execute($sql);//批量更新数据
        return true;
    }
}