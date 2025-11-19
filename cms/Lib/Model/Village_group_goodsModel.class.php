<?php

class Village_group_goodsModel extends Model
{
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

    public function arriveTime(array $goodsDetail)
    {
        //送达标题
        $arriveTime = "";
        if($goodsDetail['arrive_type'] == '1'){//当日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_cutting_time']){
                $arriveTime = date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
            else{
                $arriveTime = "明天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
        }
        if($goodsDetail['arrive_type'] == '2'){//次日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_cutting_time']){
                $arriveTime = "明天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
            else{
                $arriveTime = "后天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
        }
        if($goodsDetail['arrive_type'] == '3'){//隔日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_cutting_time']){
                $arriveTime = "后天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
            else{
                $arriveTime = "3天后".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
        }
        if($goodsDetail['arrive_type'] == '4'){//自定义
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_cutting_time']){
                $arriveTime = date("m/d",strtotime("+".$goodsDetail['arrive_days']." days"))." ".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
            else{
                $arriveTime = date("m/d",strtotime("+".($goodsDetail['arrive_days']+1)." days"))." ".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goodsDetail['arrive_time'])."送达";
            }
        }
        if($goodsDetail['arrive_type'] == '5'){//固定日期
            if(time() < $goodsDetail['arrive_cutting_datetime']){
                $arriveTime = date("m/d", $goodsDetail['arrive_datetime'])."送达";
            }
        }

        return $arriveTime;
    }

    public function getRangePickAddressGoods($lng, $lat, $distance)
    {
        $address = M('Village_group_pick_address')
            ->where([
                'is_open' => 1,
                'status' => 1,
                'delivery_radius'=> ['gt', $distance] 
            ])->field([
                'province_id','city_id','area_id',
                "(st_distance(point(`long`, `lat`), point({$lng}, {$lat}) ) * 111195 / 1000) AS distance",
            ])
            ->having("distance <= {$distance}")
            ->order('distance ASC')
            ->limit(500)
            ->select();
        $addressId = array_unique(array_merge(
            array_column($address,'province_id'),
            array_column($address,'city_id'),
            array_column($address,'area_id')
        ));
        $goodsIds = M('Village_group_goods_bind_area') 
            ->alias('ba')
            ->join(' INNER JOIN '.C('DB_PREFIX').'village_group_area ga ON ba.city_id = ga.pigcms_id')
            ->where(['area_id' => ['IN', implode(',', $addressId)]])
            ->field('goods_id')
            ->limit(300)
            ->select();
        if($goodsIds){
            return array_column($goodsIds, 'goods_id');
        }
        return [];
    }
    
    
    public function goodsList(int $distance = 0, $pageSize = 10, $lng = '', $lat = '')
    {
        $page  = $_GET['p'] ?: 1;
        $where = [
            'status' => 1,
            'start_time' => ['lt', time()],
            'end_time' => ['gt', time()]
        ];
        
        $fields = 'mer_id, goods_id, image, name, price, unit, sell_count sale_num, virtual_sell_count, arrive_type,
        arrive_cutting_datetime, arrive_datetime, arrive_days, arrive_cutting_time';

        if(!empty($lng) && !empty($lat) && $distance){
            $goodsIds = $this->getRangePickAddressGoods($lng, $lat, $distance);
            if(!empty($goodsIds)){
                $where['goods_id'] = ['in', implode(',', $goodsIds)];
            }else{
                $where['goods_id'] = ['in', '-1'];
            }
        }
        $count = $this->where($where)->count();
        $list = $this
            ->where($where)
            ->field($fields)
            ->order('sort DESC, goods_id DESC')
            ->page("{$page},{$pageSize}")
            ->select();
        
        $Page = new Page($count, $pageSize);
        $show = $Page->show();
        
        if($list) {
            foreach ($list as &$v) {
                $v['image'] = replace_file_domain($v['image']);
                $v['sale_num'] = $v['sale_num'] + $v['virtual_sell_count'];
                $v['jump_link'] = C('config.site_url').'/packapp/community_group/index.html?c=Village_group&a=index#/goods?goodsId='.$v['goods_id'];
                unset($v['virtual_sell_count']);

                $merchant = D('Merchant')
                    ->where([
                        'mer_id' => $v['mer_id']
                    ])
                    ->field('name')
                    ->find();
                $v['merchant_name'] = $merchant['name'];
                $v['arrive_time'] = $this->arriveTime($v);
            }
        }
        return [
            'count' => $count,
            'list' => $list,
            'pagebar'=>$show
        ];
    }

    public function getActivityGoods($params)
    {
        import('@.ORG.merchant_page');
        if(!empty($params['goods_id'])){
            $where['ag.goods_id'] = ['IN', [$params['goods_id']]];
        }else{
            $where = [
                'a.type' => 2,//限时抢购
                'a.status' => 1,
                'g.status' => 1,
                'a.end_time' => ['gt', strtotime(date('Y-m-d 00:00:00'))]
            ];
        }
        
        if(!empty($params['goods_name'])) {
            $where['ag.name'] = ['LIKE', '%'.$params['goods_name'].'%'];
        }

        $prefix = C('DB_PREFIX');
        $fields = "
            a.start_time active_start_time, a.end_time active_end_time, ag.goods_id, ag.buy_price discount_price, g.price, 
            g.image, g.name goods_name, g.stock_num, g.status goods_status, m.name merchant_name
        ";
        if (empty($params['goods_id'])) {
            $count = D('Village_group_activity')
                ->alias('a')
                ->join($prefix.'village_group_activity_goods ag ON a.activity_id = ag.activity_id')
                ->join($prefix.'village_group_goods g ON ag.goods_id = g.goods_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = g.mer_id')
                ->where($where)
                ->count();
            $page = new Page($count, 10);
            $list = D('Village_group_activity')
                ->alias('a')
                ->join($prefix.'village_group_activity_goods ag ON a.activity_id = ag.activity_id')
                ->join($prefix.'village_group_goods g ON ag.goods_id = g.goods_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = g.mer_id')
                ->where($where)
                ->field($fields)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        } else {
            $list = D('Village_group_activity')
                ->alias('a')
                ->join($prefix.'village_group_activity_goods ag ON a.activity_id = ag.activity_id')
                ->join($prefix.'village_group_goods g ON ag.goods_id = g.goods_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = g.mer_id')
                ->where($where)
                ->field($fields)
                ->select();
        }
        $tm = time();
        foreach ($list as &$v){
            $v['image'] = replace_file_domain($v['image']);
            $v['active_start_time'] = date('Y-m-d H:i:s', $v['active_start_time']);
            $v['active_end_time'] = date('Y-m-d H:i:s', $v['active_end_time']);
            $v['store_name'] = '';
            $v['url'] = C('config.site_url'). "/packapp/community_group/index.html?c=Village_group&a=index#/goods?goodsId={$v['goods_id']}";

            if ($tm < strtotime($v['active_start_time'])) {
                $v['status'] = 1;//待生效
            } else if ($tm > strtotime($v['active_start_time']) && $tm < strtotime($v['active_end_time'])) {
                $v['status'] = 2;//进行中
            } else {
                $v['status'] = 3;//已结束
            }
            $v['active_end_timestamp'] = strtotime($v['active_end_time']);
            if(strtotime($v['active_end_time']) < time() || $v['status'] == 3){
                $v['over_time'] = 1;
            }
        }
        if (!empty($params['goods_id'])) {
            return $list ?: [];
        }
        
        return [
            'list' => $list,
            'page_bar' => $page->show(),
            'total_count' => (int) $page->totalRows
        ];
    }
}