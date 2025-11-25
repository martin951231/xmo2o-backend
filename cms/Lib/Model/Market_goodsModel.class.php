<?php
class Market_goodsModel extends Model
{
    public function getList($data, $type, $is_shop_goods=false)
    {
        
        $where = array();
        $sqlWhere = ' WHERE 1 ';
        if ($type == 'my') {
            import('@.ORG.merchant_page');
            if (!isset($data['mer_id']) || empty($data['mer_id'])) {
                return false;
            }
            $where['mer_id'] = $data['mer_id'];
            $sqlWhere .= ' AND g.mer_id=' . $data['mer_id'];
        } elseif ($type == 'app_my') {
            import('@.ORG.merchant_page');
            if (!isset($data['mer_id']) || empty($data['mer_id'])) {
                return false;
            }
            $where['mer_id'] = $data['mer_id'];
            $sqlWhere .= ' AND g.mer_id=' . $data['mer_id'];
        } elseif ($type == 'market') {
            import('@.ORG.merchant_page');
            if (!isset($data['mer_id']) || empty($data['mer_id'])) {
                return false;
            }
            $where['mer_id'] = array('neq', $data['mer_id']);
            $where['_string'] = "(`is_add_white_list`=0 OR FIND_IN_SET('".$data['mer_id']."',`white_list`))";
            $sqlWhere .= ' AND g.mer_id<>' . $data['mer_id'];
            $sqlWhere .= " AND (`g`.`is_add_white_list`=0 OR FIND_IN_SET('" . $data['mer_id'] ."',`g`.`white_list`))";
            
            if (isset($data['cat_fid']) && $data['cat_fid'] && isset($data['cat_id']) && $data['cat_id']) {
                $where['cat_fid'] = $data['cat_fid'];
                $where['cat_id'] = $data['cat_id'];
                $sqlWhere .= ' AND g.cat_fid=' . $data['cat_fid'] . ' AND g.cat_id=' . $data['cat_id'];
            }
            if(isset($data['goods_name']) && $data['goods_name']){
                $sqlWhere .= " AND g.name LIKE '%{$data['goods_name']}%'";
            }
        } else {
            import('@.ORG.system_page');
            if (isset($data['key']) && $data['key']) {
//                $where['name'] = array('like', '%' . $data['key'] . '%');

                if(!empty($where['_string'])){
                    $where['_string'].= " AND trim(replace(g.name,' ','')) LIKE trim(replace('%{$data['key']}%',' ',''))";
                }else{
                    $where['_string']= " trim(replace(g.name,' ','')) LIKE trim(replace('%{$data['key']}%',' ',''))";
                }

                $sqlWhere = " AND trim(replace(g.name,' ','')) LIKE trim(replace('%{$data['key']}%',' ',''))";

//                select * from table where trim(replace(title,' ','')) like trim(replace('%李杨 技术博客%',' ',''));

            }
        }
        if (isset($data['status']) && $data['status'] != -1) {
            $where['status'] = $data['status'];
            $sqlWhere .= " AND g.status=" . $data['status'];
        }
        
        if (isset($data['province_id']) && $data['province_id']) {
            $where['province_id'] = $data['province_id'];
            $sqlWhere .= " AND g.province_id=" . $data['province_id'];
        }
        
        if (isset($data['city_id']) && $data['city_id']) {
            $where['city_id'] = $data['city_id'];
            $sqlWhere .= " AND g.city_id=" . $data['city_id'];
        }
        
        if (isset($data['area_id']) && $data['area_id']) {
            $where['area_id'] = $data['area_id'];
            $sqlWhere .= " AND g.area_id=" . $data['area_id'];
        }

        if($data['_string'] && !empty($data['_string'])){
            if(!empty($where['_string'])){
                $where['_string'].=' and '.$data['_string'];
            }else{
                $where['_string']=$data['_string'];
            }
            $sqlWhere.=' and '.$data['_string'];
        }
        $count = $this->alias('g')->where($where)->count();
        $p = new Page($count, 20);
        
        $sql = 'SELECT g.*, m.name AS merchant_name, m.phone AS merchant_phone, s.name AS store_name, s.phone AS store_phone'; 
        $sql .= ' FROM ' . C('DB_PREFIX') . 'market_goods AS g INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON g.store_id=s.store_id INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON s.mer_id=m.mer_id AND m.status=1';
        $sql .= $sqlWhere;
        $sql .= ' ORDER BY g.sort DESC,g.sell_count DESC,g.last_time DESC';
        $sql .= ' LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $orders = $this->query($sql);
        $goods_image_class = new goods_image();
        $catids = array();
        $d_store = M('Merchant_store');
        foreach ($orders as &$order) {
            if (!in_array($order['cat_id'], $catids)) {
                $catids[] = $order['cat_id'];
            }
            if (!in_array($order['cat_fid'], $catids)) {
                $catids[] = $order['cat_fid'];
            }
            if(!empty($order['image'])){
                $tmp_pic_arr = explode(';', $order['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $order['pic'] || $order['pic'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $order['discount_info_txt'] = L_('暂无优惠');
            if ($order['discount_info']) {
                $order['discount_info_txt'] = '';
               $order['discount_info'] = json_decode($order['discount_info'], true);
               foreach ($order['discount_info'] as $row) {
                   $order['discount_info_txt'] .= L_("批发满：X1，享受：X2折优惠<br/>",array("X1" => $row['num'] . $order['unit'],"X2" => $row['discount'])); 
               }
            }
            if ($is_shop_goods) {
                $order['shop_goods_price'] = M('Shop_goods')->where(array('goods_id' => $order['goods_id']))->getField('price');
                if ($order['store_id']>0) {
                    $merchant_store = $d_store->field('name, logo')->where(array('store_id' => $order['store_id']))->find();
                    $merchant_store['logo'] = replace_file_domain($merchant_store['logo']);
                    $merchant_store['name'] = mb_substr($merchant_store['name'], 0,8);
                    $order['merchant_store_info'] = $merchant_store;
                }
            }
        }
        if ($catids) {
            $items = D('Goods_wholesale_category')->field('id, name')->where(array('id' => array('in', $catids)))->order('`sort` DESC, `id` ASC')->select();
            $catList = array();
            foreach ($items as $item) {
                $catList[$item['id']] = $item['name'];
            }
            
            foreach ($orders as &$row) {
                $row['cat_fname'] = isset($catList[$row['cat_fid']]) ? $catList[$row['cat_fid']] : '';
                $row['cat_name'] = isset($catList[$row['cat_id']]) ? $catList[$row['cat_id']] : '';
            }
        }
        
        return array('orders' => $orders, 'totalPage' => $p->totalPage, 'pagebar' => $p->show());
    }
    
    
    public function getAll($param)
    {
        ;
    }
    public function format_spec_value($str, $goods_id, $is_prorerties = 1)
    {
//         if ($is_prorerties || $str) {
//             $properties_obj = M('Market_goods_properties');
//             $goods_properties_temp = $properties_obj->field(true)->where(array('goods_id' => $goods_id))->order('id asc')->select();
//             $goods_properties_list = array();
//             foreach ($goods_properties_temp as $goods_p) {
//                 $goods_p['val'] = explode(',', $goods_p['val']);
//                 $goods_properties_list[$goods_p['id']] = $goods_p;
//             }
//             unset($goods_properties_temp);
//         }
    
        if ($str) {
            $spec_obj = M('Market_goods_spec'); //规格表
            $spec_value_obj = M('Market_goods_spec_value');//规格对应的属性值
            $goods_spec_temp = $spec_obj->field(true)->where(array('goods_id' => $goods_id))->order('id ASC')->select();
            $goods_spec_list = array();
            $specids = array();
            foreach ($goods_spec_temp as $goods_t) {
                $specids[] = $goods_t['id'];
                $goods_spec_list[$goods_t['id']] = $goods_t;
            }
            unset($goods_spec_temp);
            $spec_valuse_list = array();
            if ($specids) {
                $spec_valuse_temp = $spec_value_obj->field(true)->where(array('sid' => array('in', $specids)))->order('id ASC')->select();
                foreach ($spec_valuse_temp as $v_temp) {
                    $spec_valuse_list[$v_temp['id']] = $v_temp;
                    $goods_spec_list[$v_temp['sid']]['list'][$v_temp['id']] = $v_temp;
                }
                unset($spec_valuse_temp, $specids);
            }
            	
            $return = array();
            $json = array();
            // 		if ($str) {
            $spec_array = explode('#', $str);
            $p_ids = array();
            $is_sort = true;
            $new_goods_spec_list = array();
            foreach ($spec_array as $row) {
                $row_array = explode('|', $row);
                $spec_ids = explode(':', $row_array[0]);
                $t_index = '';
                $t_pre = '';
                $spec_data = array();
                foreach ($spec_ids as $id) {
                    $t_index .= $t_pre . 'id_' . $id;
                    $t_pre = '_';
                    $spec_data[] = array('spec_val_id' => $id, 'spec_val_name' => isset($spec_valuse_list[$id]['name']) ? $spec_valuse_list[$id]['name'] : '');
                    if ($is_sort && isset($spec_valuse_list[$id]['sid']) && isset($goods_spec_list[$spec_valuse_list[$id]['sid']])) {
                        $new_goods_spec_list[] = $goods_spec_list[$spec_valuse_list[$id]['sid']];
                    }
                }
                $is_sort = false;
                $index = implode('_', $spec_ids);
    
                $return[$index]['index'] = $t_index;
                $return[$index]['spec'] = $spec_data;
    
                $prices = explode(':', $row_array[1]);
                $return[$index]['old_price'] = floatval($prices[0]);
                $return[$index]['price'] = floatval($prices[1]);
                $return[$index]['min_num'] = intval($prices[2]);
                $return[$index]['buy_num'] = intval($prices[2]);
                $return[$index]['stock_num'] = $prices[3];
                $return[$index]['cost_price'] = isset($prices[4]) ? $prices[4] : 0;
                $return[$index]['group_team_price'] = isset($prices[5]) ? $prices[5] : '';
                $return[$index]['group_join_price'] = isset($prices[6]) ? $prices[6] : '';

                $return[$index]['vip_wholesale_price'] = isset($prices[7]) ? $prices[7] : 0;
                $return[$index]['goods_weight'] = isset($prices[8]) ? $prices[8] : 0;
                $return[$index]['goods_volume'] = isset($prices[9]) ? $prices[9] : 0;

                $return[$index]['number'] = isset($row_array[3]) ? $row_array[3] : '';
                
    
                if (isset($row_array[2]) && $row_array[2] && strstr($row_array[2], '=')) {
                    $p_data = array();
                    $tdata = array();
                    $properties = explode(':', $row_array[2]);
                    foreach ($properties as $k => $pro) {
                        $pro_array = explode('=', $pro);
                        $p_data[] = array('id' => intval($pro_array[0]), 'num' => intval($pro_array[1]), 'name' => isset($goods_properties_list[$pro_array[0]]['name']) ? $goods_properties_list[$pro_array[0]]['name'] : '');
                        $tdata['num' . $k . '[]'] = $pro_array[1];
                    }
                    $return[$index]['properties'] = $p_data;
                    $json[$t_index] = $tdata;
                }
                if (empty($return[$index]['number']) && isset($row_array[2]) && $row_array[2] && !strstr($row_array[2], '=')) {
                    $return[$index]['number'] = $row_array[2];
                }
                $json[$t_index]['old_prices[]'] = $prices[0];
                $json[$t_index]['prices[]'] = $prices[1];
                $json[$t_index]['min_num[]'] = $prices[2];
                $json[$t_index]['stock_nums[]'] = $prices[3];
                $json[$t_index]['numbers[]'] = isset($row_array[3]) ? $row_array[3] : '';
                $json[$t_index]['group_team_price[]'] = $prices[5];
                $json[$t_index]['group_join_price[]'] = $prices[6];

            }
        }
    
        $data = array();
        $new_goods_spec_list && $data['spec_list'] = $new_goods_spec_list;
//         $goods_properties_list && $data['properties_list'] = $goods_properties_list;
        $return && $data['list'] = $return;
        $json && $data['json'] = $json;
        return $data = $data ? $data : null;
        // 		return array('spec_list' => $goods_spec_list, 'properties_list' => $goods_properties_list, 'list' => $return, 'json' => $json);
    }

    /*批发市场拼团*/

    /*校验是否可发起拼团*/
    public function check_start_group($info){
        $now_time=time();
        if($info['is_group']==1){
            if($info['group_stime']>$now_time || $info['group_ftime']<$now_time){
                $info['is_group']=0;
            }else{
                $info['clock_time']=$info['group_ftime']-$now_time;
            }
        }
        return $info;
    }

    /*校验是否可参团*/
    public function check_join_group($info,$team_id,$type=1,$mer_id=null){
        $model=M("Market_group_team");
        $team_info=$model->where(array('id'=>$team_id))->find();
        if(empty($team_info)){
            return array('error'=>1,'msg'=>L_('该拼团团队不存在！'),'data'=>$info);
        }
        if($team_info['status']==1){
            return array('error'=>1,'msg'=>L_('抱歉，该团队已成团，请参加其他团队的拼团！'),'data'=>$info);
        }elseif($team_info['status']==2){
            return array('error'=>1,'msg'=>L_('抱歉，该拼团已过期，请参加其他团队的拼团！'),'data'=>$info);
        }
        if($team_info['complete_num']<=$team_info['num']){
            $data=array();
            $data['id']=$team_id;
            $data['status']=1;
            $data['update_time']=$now_time;
            $model->data($data)->save();
            return array('error'=>1,'msg'=>L_('抱歉，该团队已成团，请参加其他团队的拼团！'),'data'=>$info); 
        }
        if($team_info['start_time']+$info['group_affect_time']<$now_time){
            $data=array();
            $data['id']=$team_id;
            $data['status']=2;
            $data['update_time']=$now_time;
            $model->data($data)->save();
            return array('error'=>1,'msg'=>L_('该团队已过期，请参加其他团队的拼团！'),'data'=>$info);
        }
        $now_time=time();
        $info['clock_time']=$team_info['start_time']+$info['group_affect_time']-$now_time;
        $info['distance_num']=$team_info['complete_num']-$team_info['num'];
        if($type==1){
            $mer_arr=M('Market_group_mer')->where(array('team_id'=>$team_id))->getfield('mer_id',true);    
            if(is_array($mer_arr) && in_array($mer_id, $mer_arr)){
                return array('error'=>1,'msg'=>L_('您已参加过该团，请勿重复参加！'),'data'=>$info);
            }
        }
        $info['is_group']=1;
        return array('error'=>0,'msg'=>'','data'=>$info);
    }

    //判断是否可以按当前价格支付
    public function check_group_act($group_id,$team_id){
        if($group_id>0){
            $info=$this->where(array('mid'=>$group_id))->find();
            $now_time=time();
            if($team_id>0){
                $result=$this->check_join_group($info,$team_id,2);
                if($result['error']==1){
                    return $result;
                }
            }else{
                if($info['is_group']==0){
                    return array('error'=>1,'msg'=>L_('抱歉，该拼团订单已失效！'));
                }
                if($info['group_stime']>$now_time || $info['group_ftime']<$now_time){
                    return array('error'=>1,'msg'=>L_('抱歉，该拼团订单已失效!！'));
                }
            }
        }        
        return $info;
    }

    //判断当前订单是否成团
    public function check_group_team($now_order,$goods_info){
        $group_id=$now_order['group_id'];
        if($group_id<=0){
            return $now_order;
        }
        $team_id=$now_order['team_id'];
        $group_type=$now_order['group_type'];
        $now_time=time();
        $model=M('Market_group_team');
        if($group_type==1){
            $data=array();
            $data['group_id']=$goods_info['mid'];
            $data['sell_mer_id']=$now_order['sell_mer_id'];
            $data['team_mer_id']=$now_order['mer_id'];
            $data['start_time']=$now_time;
            $data['complete_num']=$goods_info['group_complete_num'];
            $data['status']=0;
            $data['update_time']=$now_time;
            $team_id=$model->data($data)->add();
            //更新团购订单分组
            $data2=array();
            $data2['order_id']=$now_order['order_id'];
            $data2['team_id']=$team_id;
            M('Market_order')->data($data2)->save();
        }
        $mer_model=M('Market_group_mer');
        $mer_data=array();
        $mer_data['group_id']=$group_id;
        $mer_data['team_id']=$team_id;
        $mer_data['mer_id']=$now_order['mer_id'];
        $mer_data['order_id']=$now_order['order_id'];
        $mer_data['addtime']=$now_time;
        $res=$mer_model->data($mer_data)->add();
        if($res){
            $team_info=$model->where(array('id'=>$team_id))->find();
            $data=array();
            $data['id']=$team_id;
            $data['num']=$team_info['num']+1;
            $data['update_time']=$now_time;
            if($team_info['status']==0 && $team_info['complete_num']<=$data['num']){
                $data['status']=1;
                $this->send_group_msg($now_order['sell_mer_id'],$team_id);
            }
            $res2=$model->data($data)->save();
        }else{
            return array('error'=>1,'msg'=>L_('拼团失败，请重试！'));
        }
    }

    /*给批发商品的商家推送发货的信息同时给订单返佣*/
    public function send_group_msg($sell_mer_id,$team_id){
        $mobile=M('merchant')->where(array('mer_id'=>$sell_mer_id))->getfield('phone');
        if(empty($mobile)){
            return false;
        }
        $list=M('Market_group_mer')->alias('mer')->join('inner join '.C('DB_PREFIX').'market_order ord on mer.order_id=ord.order_id')->where(array('mer.team_id'=>$team_id))->getfield('ord.mer_id,sell_mer_id,name,num,unit,ord.order_id,ord.money',true);
        if(empty($list))return false;
        $mer_arr=array_keys($list);
        $mer_list=M('merchant')->where(array('mer_id'=>array('in',$mer_arr)))->getfield('mer_id,name,mer_spread_code,reg_time',true);
        $sms_data=array();
        $sms_data['store_id']=0;
        $sms_data['type']='market';
        $sms_data['uid']=0;
        $sms_data['mobile']=$mobile;
        $sms_data['sendto']='merchant';
        foreach ($list as $k => $v) {
            //商家信息
            $mer_info=$mer_list[$k];

            $sms_data['mer_id']=$v['sell_mer_id'];
            $sms_data['content'] = L_("商家X1批发了X2共X3订单号：X4请您注意查看并处理!",array("X1" => $mer_info['name'],"X2" => $v['name'],"X3" => $v['num'] . $v['unit'],"X4" => $v['order_id'])); 
            Sms::sendSms($sms_data);
            if($mer_info['mer_spread_code'] && C('config.merchant_recommend_wholesale_switch')==1 && C('config.merchant_recommend_spread_ratio')>0){
                $this->order_back_yongjin($mer_info,$v);
            }
        }
    }

    /*订单返佣*/
    public function order_back_yongjin($mer_info,$now_order){
        $spread_merchant=M('Merchant')->where(array('mer_code'=>$mer_info['mer_spread_code'],'mer'=>array('neq',$mer_info['mer_id'])))->find();
        if($spread_merchant){
            $merchant_recommend_spread_ratio = floatval(C('config.merchant_recommend_spread_ratio') * 0.01);
            // 计算分佣金额
            $spread_money = $now_order['money'] * $merchant_recommend_spread_ratio;
            $spread_money = round($spread_money, 2); // 分佣金额

            $desc = L_("您推广的X1商家在批发市场消费X2，您获得分佣",array("X1" => $mer_info['name'],"X2" => $v['money'].C('config.Currency_txt'))); 
            $order_info=array();
            $order_info['money'] = $spread_money;
            $order_info['order_type'] = 'market_spread';
            $order_info['mer_id'] = $spread_merchant['mer_id'];
            $order_info['order_id'] = $now_order['order_id'];
            $order_info['desc'] = $desc;
            // 处理数量
            if ($total_num > 0) {
                $order_info['num'] = $total_num;
            } else {
                $order_info['num'] = $now_order['num'];
            }
            D('Merchant_money_list')->add_money($order_info);
            // 同步记录到记录表格中
            $order_info['spread_mer_id'] = $mer_info['mer_id'];
            $order_info['spread_mer_name'] = $mer_info['name'];
            $order_info['spread_time'] = $mer_info['reg_time'];
            $order_info['add_time'] = time();
            $res=M('Merchant_recommend_merchant_record')->data($order_info)->add();
            return $res;
        }
        return true;
    }

    //获取最新拼团状态,返回正确的团队状态
    public function get_team_status($team_id){
        $now_time=time();
        $team_info=M('Market_group_team')->alias('team')->join('inner join '.C('DB_PREFIX').'market_goods gd on team.group_id=gd.mid')->where(array('team.id'=>$team_id))->field('team.id,team.sell_mer_id,team.complete_num,team.num,team.status,team.start_time,gd.group_affect_time')->find();
        if($team_info['status']==0){
            $data=array();
            $data['id']=$team_id;
            $data['update_time']=$now_time;
            if($team_info['complete_num']<=$team_info['num']){                
                $data['status']=1;
                $res=M('Market_group_team')->data($data)->save();
                if($res){
                    $team_info['status']=1;
                    $this->send_group_msg($team_info['sell_mer_id'],$team_id);
                }
            }elseif($team_info['start_time']+$team_info['group_affect_time']<$now_time){
                $data['status']=2;
                $res=M('Market_group_team')->data($data)->save();
                if($res){
                    $team_info['status']=2;
                }
            }
        }
        return $team_info['status'];
    }
}