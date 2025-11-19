<?php
/*
 * 订单详情
 * */
class OrderSearchAction extends BaseAction{
    public function shop_list(){
        $phone = $_GET['originCallNo']?$_GET['originCallNo']:$_GET['phone'];
        if(empty($phone)){
            $this->error('手机号码不能为空');
        }
        $phone = htmlspecialchars($phone);
		$_GET['phone'] = $phone;
        $user = D('User')->get_user($phone,'phone');
        $where['_string'] = " userphone={$phone} OR uid={$user['uid']} ";
        $count_where = " `o`.`userphone`='" . $phone. "' OR `o`.`uid`={$user['uid']} ";

        $order_sort = 'order_id DESC';

        $result = D("Shop_order")->get_order_list($where, $order_sort);
        $list = isset($result['order_list']) ? $result['order_list'] : '';

        $status = array('商家审核中', '已完成退货', '商家拒绝退货', '商家审核中', '取消退货申请');
        foreach ($list as &$l) {
            $order_id = $l['order_id'];

            $supplys =  M('deliver_supply')->where(array('order_id'=>intval($l['order_id'])))->find();

            $l['deliver_user']  = M('Deliver_user')->field('name,phone')->where(array('uid'=>$supplys['uid']))->find();
            $l['merchant'] = M('Merchant')->field('name')->where(array('mer_id'=>$l['mer_id']))->find();
            $l['store_'] = M('Merchant_store')->field('name,phone')->where(array('store_id'=>$l['store_id']))->find();


            $supplys['confirm_time'] = $supplys['create_time'] && $supplys['order_time'] ? intval(($supplys['create_time'] - $supplys['order_time']) / 60) . '分钟' : '-';
            $supplys['grab_time'] = $supplys['start_time'] ? intval(($supplys['start_time'] - $supplys['create_time']) / 60) . '分钟' : '-';
            $supplys['deliver_use_time'] = $supplys['end_time'] ? intval(($supplys['end_time'] - $supplys['start_time']) / 60) . '分钟' : '-';
            $tempList = array();
            $times = M('Shop_order_log')->where(array('order_id'=>$l['order_id']))->select();
            $delivery_times = array();
            foreach($times as $time){
                if($time['status']>3 && $time['status']<6){
                    $delivery_times[] = $time;
                }
            }
            if(count($delivery_times)>0){
                $l['delivery_times'] = $delivery_times;
            }
            $l['info'] = M('Shop_order_detail')->field(true)->where(array('order_id' =>  $order_id))->select();

            foreach($l['info'] as $v) {
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
            $l['info'] = $tempList;
            $l['supplys'] = $supplys;
            $l['store'] =  D('Merchant_store_shop')->field(true)->where(array('store_id' => $l['store_id']))->find();

            //退款详情
            $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'uid' => $l['uid']))->order('id DESC')->select();

            $sql = "SELECT g.image, d.goods_id, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
            $lists = D()->query($sql);


            $data = array();
            $goods_image_class = new goods_image();
            foreach ($lists as $row) {
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

            foreach ($refundList as &$refund) {
                $refund['image'] = $refund['image'] ? explode(',', $refund['image']) : '';
                $refund['goodsList'] = isset($data[$refund['id']]) ? $data[$refund['id']] : array();
                $refund['showStatus'] = $status[$refund['status']];
            }
            $l['refund_list'] = $refundList;
        }

        $this->assign('list',$list);
        $this->assign('pagebar',$result['pagebar']);

        $this->display();
    }

    public function group_list(){
        $phone = $_GET['originCallNo']?$_GET['originCallNo']:$_GET['phone'];
        if(empty($phone)){
            $this->error('手机号码不能为空');
        }
        $phone = htmlspecialchars($phone);
        $condition_where = "o.phone={$phone}";
        $order_count =M('Group_order')->join('as o LEFT JOIN '.C('DB_PREFIX').'group as g ON g.group_id = o.group_id LEFT JOIN '
            .C('DB_PREFIX').'user as u ON o.uid=u.uid LEFT JOIN '.C('DB_PREFIX').'merchant as m ON m.mer_id=o.mer_id')->where($condition_where)->count();
        import('@.ORG.user_page');
        $p = new Page($order_count,10);
        $order_list  =M('Group_order')->join('as o LEFT JOIN '.C('DB_PREFIX').'group as g ON g.group_id = o.group_id LEFT JOIN '
            .C('DB_PREFIX').'user as u ON o.uid=u.uid LEFT JOIN '.C('DB_PREFIX').'merchant as m ON m.mer_id=o.mer_id')->where($condition_where)->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`g`.`price` as g_price,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`m`.`phone` as m_phone,`m`.`name` as m_name,`m`.`mer_id`,`g`.`group_id`')->where($condition_where)->order('o.order_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($order_list as &$v) {
            if($v['store_id']){
                $now_store = D('Merchant_store')->field('`name`')->where(array('store_id'=>$v['store_id']))->find();
                $v['store_name'] = $now_store['name'];
            }
            $pass_array = D('Group_pass_relation')->get_pass_array($v['order_id']);
            $v['pass_array'] = $pass_array;

            if($v['refund_detail']){
                $v['refund_detail'] = unserialize($v['refund_detail']);
            }

            if($v['trade_info']){
                $trade_info_arr = unserialize($v['trade_info']);
                if($trade_info_arr['type'] == 'hotel'){
                    $trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($v['trade_info']);
                    $v['trade_hotel_info']=$trade_hotel_info;
                }
            }
        }
        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_list',$order_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->display();
    }

    public function meal_list(){
        $phone = $_GET['originCallNo']?$_GET['originCallNo']:$_GET['phone'];
        if(empty($phone)){
            $this->error('手机号码不能为空');
        }
        $where['userphone'] =  htmlspecialchars($phone);
        $count_where = " `o`.`userphone`='" .  $phone . "'";

        $order_sort = 'order_id DESC';

        $where_store = array('status' => 1);

        $condition_where = 'WHERE s.status=1';

        $sql_cont = 'SELECT count(distinct(o.order_id)) as count from pigcms_merchant as m';
        $sql_cont .= ' INNER JOIN pigcms_merchant_store as s ON m.mer_id=s.mer_id';
        $sql_cont .= ' INNER JOIN pigcms_foodshop_order as o ON o.store_id=s.store_id LEFT JOIN';
        $sql_cont .= " pigcms_plat_order AS p ON o.order_id=p.business_id AND p.business_type='foodshop' ";
        $sql_cont .= $condition_where;

        $count = M()->query($sql_cont);
        $count = $count[0]['count'];
        import('@.ORG.user_page');
        $p = new Page($count, 10);

        $sql = 'SELECT o.*, p.pay_type as pay_method, s.name as store_name, m.name as merchant_name from pigcms_merchant as m';
        $sql .= ' INNER JOIN pigcms_merchant_store as s ON m.mer_id=s.mer_id';
        $sql .= ' INNER JOIN pigcms_foodshop_order as o ON o.store_id=s.store_id';
        $sql .= " LEFT JOIN pigcms_plat_order AS p ON p.business_type='foodshop' AND o.order_id=p.business_id ";
        $sql .= $condition_where . ' GROUP BY o.order_id ORDER BY o.order_id DESC, p.order_id DESC';
        $sql .= ' limit ' . $p->firstRow . ',' . $p->listRows;
        $list = M()->query($sql);

        $mer_ids = $store_ids = array();
        foreach ($list as $l) {
            $table_types[] = $l['table_type'];
            $tids[] = $l['table_id'];

        }


        $type_list = array();
        if ($table_types) {
            $temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
            foreach ($temp_type_list as $tmp) {
                $type_list[$tmp['id']] = $tmp;
            }
        }
        $table_list = array();
        if ($tids) {
            $temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
            foreach ($temp_table_list as $temp) {
                $table_list[$temp['id']] = $temp;
            }
        }

        foreach ($list as &$li) {
            $li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$li['table_type']]['name'] . '(' . $type_list[$li['table_type']]['min_people'] . '-' . $type_list[$li['table_type']]['max_people'] . '人)' : '';
            $li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
            $li['show_status'] = D('Foodshop_order')->status_list[$li['status']];
        }

        $pagebar = $p->show();

        $this->assign('status_list', D('Foodshop_order')->status_list);
        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);

        foreach ($list as &$item) {
            $where['order_id'] = $item['order_id'];
            $item['order_detail'] = D('Foodshop_order')->get_order_detail($where, 3);
        }

        $this->assign('order_list',$list);
        $this->assign('pagebar',$pagebar);
        $this->display();
    }

    //服务快派
    public function service_list(){
        $phone = $_GET['originCallNo']?$_GET['originCallNo']:$_GET['phone'];
        if(empty($phone)){
            $this->error('手机号码不能为空');
        }
        $where = "  u.phone LIKE '%".htmlspecialchars($phone)."%' ";



        //搜索

        $serviceOfferCount = D('Service_user_publish')->where($where)->join('AS s LEFT JOIN '.C('DB_PREFIX').'user AS u ON u.uid=s.uid ')->count();

        import('@.ORG.user_page');
        $p = new Page($serviceOfferCount, 10);
        $serviceOfferList =  D('Service_user_publish')->join('AS s LEFT JOIN '.C('DB_PREFIX').'user AS u ON u.uid=s.uid ')
            ->limit($p->firstRow . ',' . $p->listRows)->where($where)
            ->field('s.*,u.nickname,u.phone')->order('s.publish_id desc')->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        foreach ($serviceOfferList as $key => $value) {

            $publishInfo = $value;
            if($publishInfo['catgory_type'] == 2){
                $publish_total_price = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->field('total_price')->find();
                $serviceOfferList[$key]['price'] = $publish_total_price['total_price'];
            }else if($publishInfo['catgory_type'] == 3){
                $publish_total_price = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->field('total_price')->find();
                $serviceOfferList[$key]['price'] = $publish_total_price['total_price'];
            }
            $serviceOfferList[$key]['catgory_type'] = $publishInfo['catgory_type'];
            $serviceOfferList[$key]['cid'] = $publishInfo['cid'];
            $categoryInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))
                ->field('cid,cat_name,cut_proportion,return_integral_proportion,accept_time')->find();
            $serviceOfferList[$key]['cat_name'] = $categoryInfo['cat_name'];

            $userInfo = D('User')->where(array('uid'=>$publishInfo['uid']))->find();
            $serviceOfferList[$key]['nickname'] = $userInfo['nickname'];
            $publishInfo['register_phone'] = $userInfo['phone'];
            $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
            $providerInfo = D('Service_provider')->where(array('uid'=>$value['p_uid']))->field('name')->find();
            $serviceOfferList[$key]['provider_name'] = $providerInfo['name'];
            $serviceOfferList[$key]['publishInfo'] = $publishInfo;

            if($publishInfo['catgory_type'] == 1){

                foreach ($publishInfo['cat_field'] as $kk => $vv) {
                    if($vv['type'] == 7){
                        $publishInfo['address_start'] = $vv['value']['address_start'];
                        $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
                        $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
                        $publishInfo['address_end'] = $vv['value']['address_end'];
                        $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
                        $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
                    }
                }
                unset($publishInfo['cat_field']['cid']);

                $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))
                    ->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")
                    ->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->select();
                foreach ($offer_list as $key => $value) {
                    $offer_list[$key]['msg_list'] = D('Service_offer_message')
                        ->where(array('offer_id'=>$value['offer_id']))->field('message,type,add_time')->order('add_time asc')->select();
                    $offer_list[$key]['msg_count'] = count($offer_list[$key]['msg_list']);

                }
                $serviceOfferList[$key]['offer_list'] = $offer_list;
                $serviceOfferList[$key]['offer_count'] = count($offer_list);

            }else if($publishInfo['catgory_type'] == 2){
                $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();

                $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
                // 判断表中获取到地址id 获取对应id的地址信息写入，否则取表中记录的地址
                if ($cat_field_info['adress_id']) {
                    $adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['adress_id']);
                    $cat_field_info['end_adress_name'] = $adress_info['adress'].$adress_info['detail'];
                } else {
                    $cat_field_info['end_adress_name'] = $cat_field_info['end_adress_name'];
                }

                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))
                    ->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")
                    ->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

                $serviceOfferList[$key]['offer_info'] = $offer_info;
                $serviceOfferList[$key]['cat_field_info'] = $cat_field_info;

            }else if($publishInfo['catgory_type'] == 3){
                $categoryInfo['accept_time']*60*60;
                $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))
                    ->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")
                    ->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

                // 判断表中获取到地址id 获取对应id的地址信息写入，否则取表中记录的地址
                if ($cat_field_info['adress_id']) {
                    $start_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['start_adress_id']);
                    $cat_field_info['start_adress_name'] = $start_adress_info['adress'].$start_adress_info['detail'];
                    $end_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['end_adress_id']);
                    $cat_field_info['end_adress_name'] = $end_adress_info['adress'].$end_adress_info['detail'];
                } else {
                    $cat_field_info['start_adress_name'] = $cat_field_info['start_adress_name'];
                    $cat_field_info['end_adress_name'] = $cat_field_info['end_adress_name'];
                }

                $serviceOfferList[$key]['offer_info'] = $offer_info;
                $serviceOfferList[$key]['cat_field_info'] = $cat_field_info;
            }
        }
        $this->assign('list',$serviceOfferList);
        $this->display();
    }

    //到店付
    public function store_list(){
        $phone = $_GET['originCallNo']?$_GET['originCallNo']:$_GET['phone'];
        if(empty($phone)){
            $this->error('手机号码不能为空');
        }
        $phone = htmlspecialchars($phone);
        $store_order = M('Store_order');
        import('@.ORG.user_page');
        $where = array('paid' => 1);
        $where['u.phone']  =$phone;
        $sql_count = "SELECT count(s.order_id) as count FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND u.phone='".$phone."'  ORDER BY s.order_id DESC ";

        $count = M()->query($sql_count);
        $count = $count[0]['count'];

        $p = new Page($count, 10);

        $sql = "SELECT s.*, u.nickname, u.phone, m.name AS merchant_name, ms.name AS store_name FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND  u.phone='".$phone."' ORDER BY s.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
        $order_list = M()->query($sql);

        foreach ($order_list as &$l) {
            $l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay']);
        }
        $pagebar = $p->show();

        $this->assign(array('list' => $order_list, 'pagebar' => $pagebar));
        $this->display();
    }
}