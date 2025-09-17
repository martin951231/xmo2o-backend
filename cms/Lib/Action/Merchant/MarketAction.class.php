<?php
class MarketAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->config['is_open_market'])) {
            $this->error(L_('平台没有开启进销存功能'));
            exit;
        }
    }
    const GOODS_SORT_LEVEL = 3;
    
    public function index()
    {
        $result = D('Market_goods')->getList(array('mer_id' => $this->merchant_session['mer_id']), 'my');
        $this->assign($result);
        $this->display();
    }
    public function market()
    {
        $type=I('get.type','','trim');
        if($type=='group'){
            $this->redirect('buy',$_GET);
        }

        $cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
        $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        $province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;
        $city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $where = array('mer_id' => $this->merchant_session['mer_id'], 'status' => 1);
        $where['province_id'] = $province_id;
        $where['city_id'] = $city_id;
        $where['area_id'] = $area_id;
        if ($cat_fid && $cat_id) {
            $where['cat_fid'] = $cat_fid;
            $where['cat_id'] = $cat_id;
        }
        if($_GET['goods_name']){
            $where['goods_name'] = $_GET['goods_name'];
        }
        $goods_type=I('goods_type','','intval');
        $now_time=time();
        if($goods_type==1){
            $where['_string']='(g.is_group=0 or (g.is_group=1 && (g.group_stime>'.$now_time.' || g.group_ftime<'.$now_time.')))';
        }elseif($goods_type){
            $where['_string']='g.is_group=1 && g.group_stime<='.$now_time.' && g.group_ftime>='.$now_time;
        }
        $result = D('Market_goods')->getList($where, 'market');
        $list=$result['orders'];
        /*不在活动时间的拼团商品按照普通商品显示*/
        $market_express_template = M('Market_express_template');
        foreach ($list as $k => $v) {
            if($v['is_group']==1){
                if($v['group_stime']>$now_time || $v['group_ftime']<$now_time){
                    $v['is_group']=0;
                }
            }
            if ($v['express_template_id'] > 0) {
                $v['express_is_type'] = $market_express_template->where(array('id' => $v['express_template_id']))->getField('is_type');
                if ($v['express_is_type']!=null) {
                    $v['express_is_type'] += 1;  // 处理后的值 1 快递和物流 2 快递 3 物流
                } else {
                    unset($v['express_is_type']);
                }
            }

            $list[$k]=$v;
        }
        fdump($list, '$list');
        $result['orders']=$list;

        if ($this->merchant_session['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant_session['type_id']))->find();
            $this->assign('merchant_type',$merchant_type);
        }

        // 批发市场上架商家积分商城权限控制
        if(C('config.merchant_integral_mall_control') && $this->merchant_session['is_publish_integral_mall']){
            $is_publish_integral_mall = 1;
        }
        $this->assign('is_publish_integral_mall', $is_publish_integral_mall);

        $this->assign($result);
        $this->assign($where);
        $category_list = D('Goods_wholesale_category')->get_list();
        $this->assign('category_list', json_encode($category_list));
        $this->cartCount();
        $this->display();
    }
    
    public function sell_order()
    {
        $data = array('mer_id' => $this->merchant_session['mer_id']);
        if (isset($_GET['goods_id']) && $_GET['goods_id']) {
            $data['goods_id'] = intval($_GET['goods_id']);
        }
        if(!empty($_GET['keyword'])){
            $data['search_type'] = $_GET['search_type'];
            $data['keyword'] = $_GET['keyword'];
        }
        if(!empty($_GET['is_pick_in_store'])){
            $data['is_pick_in_store'] = $_GET['is_pick_in_store'];
        }
        if(!empty($_GET['status'])){
            $data['status'] = $_GET['status'];
        }
        if(!empty($_GET['pay_type'])){
            $data['pay_type'] = $_GET['pay_type'];
        }
        if ($_GET['begin_time'] && $_GET['end_time']) {
            $begin_time = strtotime($_GET['begin_time']);
            $end_time = strtotime($_GET['end_time'] . '23:59:59');
            if ($begin_time > $end_time) {
                $this->error(L_('结束时间必须大于开始时间！'));
            }
            $data['begin_time'] = $begin_time;
            $data['end_time'] = $end_time;
        }
        if (isset($_GET['province_id']) && $_GET['province_id']) {
            $data['province_id'] = intval($_GET['province_id']);
        }
        if (isset($_GET['city_id']) && $_GET['city_id']) {
            $data['city_id'] = intval($_GET['city_id']);
        }
        if (isset($_GET['area_id']) && $_GET['area_id']) {
            $data['area_id'] = intval($_GET['area_id']);
        }


        $result = D('Market_order')->getList($data, 'sell');
        foreach ($result['orders'] as $k => $v) {
            if($v['group_id']>0){
                $v['group_status']=D("Market_goods")->get_team_status($v['team_id']);
                if($v['group_status']==0){
                    $v['status_txt'].='/<span style="color:red">未成团</span>';
                }elseif($v['group_status']==1){
                    $v['status_txt'].='/<span style="color:green">拼团成功</span>';
                }elseif($v['group_status']==2){
                    $v['status_txt'].='/<span style="color:red">拼团失败</span>';
                }
                //未成团或者拼团失败的订单不支持发货
                if($v['status']==1 && $v['group_status']!=1){
                    $v['status']=0;
                }
                $result['orders'][$k]=$v;
            }
        }
        $this->assign($result);

        // 支付方式
        $pay_method_all = D('Config')->get_pay_method(0,0);
        $pay_method = D('Market_order')->pay_method(true, false);
        if ($pay_method_all['weixin']) {
            $pay_method['weixin'] = $pay_method_all['weixin'];
        }
        $this->assign('pay_method', $pay_method);

        $this->display();
    }
    
    public function buy_order()
    {
        $result_list = D('Market_order')->getList(array('mer_id' => $this->merchant_session['mer_id']), 'buy');
        if(!empty($result_list['orders'])){
            $databaseMarketOrder = D('Market_order');
            foreach($result_list['orders'] as $value){
                if($value['send_time'] > 0 && $value['status'] == 2){

                    $pull_time = $this->config['merchant_integral_mall_time']*24*3600;

                    if($value['send_time'] + $pull_time < time()){

                        $where = array('order_id' => $value['order_id'], 'mer_id' => $this->merchant_session['mer_id']);
                        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
                            if ($marketOrder['status'] != 2) {
                                exit(json_encode(array('error_code' => true, 'msg' => L_('此单暂时不能收货'))));
                            } else {
                                if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))) {

                                    if ($marketOrder && $marketOrder['type']==1 && $marketOrder['integral_mall_goods_order_id']>0) {
                                        $mall_order_id = $marketOrder['integral_mall_goods_order_id'];
                                        $data = array('status' => 3);
                                        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
                                        $data['last_time'] = $_SERVER['REQUEST_TIME'];
                                        $order_info = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->find();
                                        if (D('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->save($data)) {

                                            D('Merchant_integral_mall_log')->add_log(array('order_id' => $value['order_id'], 'status' => 3, 'from_uid' => $order_info['uid'], 'note' => '用户确认收货'));

                                            // 用户确认收货-计算给予商家结算
                                            D('Merchant_integral_mall_goods_order')->order_settlement($order_info);
                                        }
                                        $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                                        $sms_data['uid'] = 0;
                                        $sms_data['mobile'] = $sellmerchant['phone'];
                                        $sms_data['sendto'] = 'merchant';
                                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$this->merchant_session['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                                        Sms::sendSms($sms_data);
                                    } else {

                                        //对方增加余额
                                        $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                                        $money = floatval($marketOrder['money']);
                                        $total_money = $money + $total_express_fee;
                                        $data = array();
                                        $data['order_type'] = 'market';
                                        $data['desc'] = L_('批发市场的商品').'【'.$marketOrder['name'].'.】'.L_('卖出时增加商家余额');
                                        $data['mer_id'] = $marketOrder['sell_mer_id'];
                                        $data['money'] = $marketOrder['money'];
                                        $data['order_id'] = $value['order_id'];
                                        $data['total_money'] = $total_money;
                                        // D('Merchant_money_list')->add_money($data);
                                        D('SystemBill')->bill_method(0,$data);

                                        $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                                        $sms_data['uid'] = 0;
                                        $sms_data['mobile'] = $sellmerchant['phone'];
                                        $sms_data['sendto'] = 'merchant';
                                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$this->merchant_session['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                                        Sms::sendSms($sms_data);
                                    }


//                                    exit(json_encode(array('error_code' => false, 'msg' => 'ok')));
                                } else {
//                                    exit(json_encode(array('error_code' => true, 'msg' => L_('收货失败，稍后重试'))));
                                }
                            }
                        } else {
//                            exit(json_encode(array('error_code' => true, 'msg' => L_('订单信息错误'))));
                        }
                    }
                }
            }
        }


//        $result = D('Market_order')->getList(array('mer_id' => $this->merchant_session['mer_id']), 'buy');
        $result = $result_list;
        
        $goodsIDs = array();
        foreach ($result['orders'] as $row) {
            $goodsIDs[] = $row['goods_id'];
        }
        
        
        $sql = "SELECT `s`.`store_id` FROM ". C('DB_PREFIX') . "merchant_store AS s INNER JOIN  ". C('DB_PREFIX') . "merchant_store_shop AS ss ON `s`.`store_id`=`ss`.`store_id`";
        $sql .= " WHERE `s`.`mer_id`={$this->merchant_session['mer_id']} AND `s`.`status`='1' AND `s`.`have_shop`='1'";
        $sql .= " ORDER BY `s`.`sort` DESC,`s`.`store_id` ASC";
        $store_list = D()->query($sql);
        $storeIDs = array();
        foreach ($store_list as $store) {
            $storeIDs[] = $store['store_id'];
        }
        
        $goods_list = M('Shop_goods')->field(true)->where(array('original_goods_id' => array('in', $goodsIDs), 'store_id' => array('in', $storeIDs)))->select();
        $goodsList = array();
        foreach ($goods_list as $goods) {
            if (isset($goodsList[$goods['original_goods_id']])) {
                $goodsList[$goods['original_goods_id']][] = $goods['store_id'];
            } else {
                $goodsList[$goods['original_goods_id']] = array($goods['store_id']);
            }
        }
        foreach ($result['orders'] as &$new) {
            $tempStoreIds = isset($goodsList[$new['goods_id']]) ? $goodsList[$new['goods_id']] : array();
            $newStoreIds = array_diff($storeIDs, $tempStoreIds);
            $new['store_count'] = count($newStoreIds);
            if (count($newStoreIds) == 1) {
                $new['to_store_id'] = array_shift($newStoreIds);
            } else {
                $new['to_store_id'] = 0;
            }
            if($new['group_id']>0){
                $new['group_status']=D("Market_goods")->get_team_status($new['team_id']);
                if($new['group_status']==0){
                    $new['status_txt'].='/<span style="color:red">未成团</span>';
                }elseif($new['group_status']==1){
                    $new['status_txt'].='/<span style="color:green">拼团成功</span>';
                }elseif($new['group_status']==2){
                    $new['status_txt'].='/<span style="color:red">拼团失败</span>';
                }
            }
        }
        // 批发市场上架店铺权限控制
        $this->assign('is_market_goods_upper_store', $this->merchant_session['is_market_goods_upper_store']?$this->merchant_session['is_market_goods_upper_store']:0);
        
        // 批发市场上架商家积分商城权限控制
        if(C('config.merchant_integral_mall_control') && $this->merchant_session['is_publish_integral_mall']){
            $is_publish_integral_mall = 1;
        }
        $this->assign('is_publish_integral_mall', $is_publish_integral_mall);

        $this->assign($result);
        $this->display();
    }
    
    public function buy_order_export()
    {   
        $param = $_POST;
        $param['type'] = 'market_buy';
        $param['order_type'] = 'buy';
        $param['rand_number'] = time();
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
        
    }
    
    public function buy()
    {
        $goodsId = intval($_GET['goods_id']);
        $order_id = intval($_GET['order_id']);
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->error(L_('商品不存在！'));
        }
        if($marketGoods['mer_id']==$this->merchant_session['mer_id']){
            $this->error(L_('自己不能批发自己发布的商品！'));
        }
        $group_id=I('group_id',0,'intval');
        $team_id=I('team_id',0,'intval');
        if(IS_POST){
            if($group_id>0){
                $result=$databaseMarketGoods->check_group_act($group_id,$team_id);
                if($result['error']==1){
                    $this->error($result['msg']);
                }
                $marketGoods=$result;
            }
        }else{
            if($team_id>0){ //参团校验
                $result=$databaseMarketGoods->check_join_group($marketGoods,$team_id,1,$this->merchant_session['mer_id']);
                if($result['error']==1){
                    $this->assign('group_error_msg',$result['msg']);
                }
                $marketGoods=$result['data'];
            }else{  //判断能否发起拼团
                $marketGoods=$databaseMarketGoods->check_start_group($marketGoods);
            }
        }

        if(!empty($marketGoods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $marketGoods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $marketGoods['pic_arr'][$key]['title'] = $value;
                $marketGoods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }
        
        $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
        $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
        $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);
        $marketGoods['username'] = $this->merchant_session['name'];
        $marketGoods['userphone'] = $this->merchant_session['phone'];
        $marketGoods['address'] = '';
        $marketGoods['buy_num'] = $marketGoods['min_num'];
        $totalMoney = 0;
        $group_totalMoney = 0;
        $totalCount = 0;
        $is_vip = false;
        $is_pick = false;
        $express = false;
        $logistics = false;
        // 配送费总值

        // 如果允许自提获取自提点信息
        if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
            $pick_info = explode(',', $marketGoods['pick_info']);
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
            $pick_address = array();
            $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : '';
            $pick_msg = array();
            foreach ($pick_list as $val) {
                if (in_array($val['pick_addr_id'], $pick_info)) {
                    unset($val['pwd']);
                    $pick_address[] = $val;
                    if ($pick_id == $val['pick_addr_id']) {
                        $pick_msg = $val;
                    }
                }
            }
            if (empty($pick_address)) {
                $is_pick = false;
            } else {
                $is_pick = true;
                $has_check = 'pick';
            }
            $this->assign('pick_address', $pick_address);
            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
            if (IS_POST && $pick_msg) {
                $_POST['username'] = $merchant['name'];
                $_POST['userphone'] = $pick_msg['phone'];
                $_POST['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                if (!empty($pick_msg['province'])) {
                    $_POST['province_id'] = $pick_msg['province'];
                }
                if (!empty($pick_msg['city'])) {
                    $_POST['city_id'] = $pick_msg['city'];
                }
                if (!empty($pick_msg['area'])) {
                    $_POST['area_id'] = $pick_msg['area'];
                }
            }
        }
        // 获取一下对应运费模板消息
        if ($marketGoods['express_template_id'] > 0) {
            $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $marketGoods['express_template_id']))->select();
            foreach ($template_msgs as $val) {
                if ($val['type']==1) {
                    $express = true;
                } elseif ($val['type']==2) {
                    $logistics = true;
                }
            }
            if ($express && $logistics) {
                $is_type = 3; // 快递和物流都存在
                if (!$has_check) {
                    $has_check = 'express';
                }
            } elseif ($express) {
                $is_type = 1; // 快递
                if (!$has_check) {
                    $has_check = 'express';
                }
            } elseif ($logistics) {
                $is_type = 2; // 物流
                if (!$has_check) {
                    $has_check = 'logistics';
                }
            } else {
                $is_type = 0; // 既没有快递也没有物流
            }
        } else {
            $is_type = 0; // 既没有快递也没有物流
        }
        $this->assign('is_pick', $is_pick);
        $this->assign('is_type', $is_type);
        $this->assign('has_check', $has_check);

        if ($this->merchant_session['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant_session['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }

        if ($marketGoods['list']) {
            foreach ($marketGoods['list']as &$vo) {
                $totalCount += $vo['min_num'];
                if ($is_vip && $vo['vip_wholesale_price']>0) {
                    $totalMoney += $vo['min_num'] * $vo['vip_wholesale_price'];
                    $vo['o_price'] = $vo['price'];
                    $vo['price'] = $vo['vip_wholesale_price'];
                } else {
                    $totalMoney += $vo['min_num'] * $vo['price'];
                }
                if($marketGoods['is_group']==1){    //拼团活动商品
                    $group_totalMoney += $vo['min_num'] * $vo['group_team_price'];
                }
                $min = $vo['min_num']; // 最少购买数量-计算下当前最少购买配送数量的配送费用
            }
        } else {
            if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                $totalMoney = $marketGoods['buy_num'] * $marketGoods['vip_wholesale_price'];
                $marketGoods['o_price'] = $marketGoods['price'];
                $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
            } else {
                $totalMoney = $marketGoods['buy_num'] * $marketGoods['price'];
            }
            $totalCount = $marketGoods['buy_num'];
            if($marketGoods['is_group']==1){    //拼团活动商品
                $group_totalMoney = $marketGoods['buy_num'] * $marketGoods['group_team_price']; 
            }
        }
        
        if ($marketOrderOld = M('Market_order')->field(true)->where(array('order_id' => $order_id, 'goods_id' => $goodsId, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->find()) {
            $totalMoney = 0;
            $totalCount = 0;
            if($marketGoods['is_group']==1){
                if ($marketOrderOld['spec_value'] && $marketGoods['list']) {
                $spec_array = explode('#', $marketOrderOld['spec_value']);
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
                        if (isset($marketGoods['list'][$i])) {
                            $tArray = explode(':', $row_array[1]);
                            $marketGoods['list'][$i]['buy_num'] = $tArray[3];
                            $totalCount += $tArray[3];
                            $totalMoney += $tArray[5] * $tArray[3];
                        }
                    }
                } else {
                    $totalCount = $marketOrderOld['num'];
                    $totalMoney = $marketOrderOld['num'] * $marketOrderOld['group_team_price'];
                }
            }else{
                if ($marketOrderOld['spec_value'] && $marketGoods['list']) {
                $spec_array = explode('#', $marketOrderOld['spec_value']);
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
                        if (isset($marketGoods['list'][$i])) {
                            $tArray = explode(':', $row_array[1]);
                            $marketGoods['list'][$i]['buy_num'] = $tArray[3];
                            $totalCount += $tArray[3];
                            if ($is_vip && $tArray[7]>0) {
                                $totalMoney += $tArray[3] * $tArray[7];
                            } else {
                                $totalMoney += $tArray[3] * $tArray[1];
                            }
                        }
                    }
                } else {
                    $totalCount = $marketOrderOld['num'];
                    if ($is_vip && $marketOrderOld['vip_wholesale_price']>0) {
                        $totalMoney = $marketOrderOld['num'] * $marketOrderOld['vip_wholesale_price'];
                        $marketOrderOld['o_price'] = $marketOrderOld['price'];
                        $marketOrderOld['price'] = $marketOrderOld['vip_wholesale_price'];
                    } else {
                        $totalMoney = $marketOrderOld['num'] * $marketOrderOld['price'];
                    }
                }
            }

            $marketGoods['desc'] = $marketOrderOld['desc'];
            $marketGoods['buy_num'] = $marketOrderOld['num'];
            $marketGoods['username'] = $marketOrderOld['username'];
            $marketGoods['userphone'] = $marketOrderOld['userphone'];
            $marketGoods['address'] = $marketOrderOld['address'];
        }

        $discount_info = null;
        foreach ($marketGoods['discount_info'] as $discount) {
            if ($discount['num'] <= $totalCount) {
                if ($discount_info) {
                    if ($discount_info['discount'] > $discount['discount']) {
                        $discount_info = $discount;
                    }
                } else {
                    $discount_info = $discount;
                }
            }
        }
        if ($discount_info) {
            $totalMoney= round($totalMoney* $discount_info['discount'] * 0.1,2);
        }
        $marketGoods['totalMoney'] = $totalMoney;
        $marketGoods['group_totalMoney'] = $group_totalMoney;

        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant_session['wholesale_discount']>0 ? $this->merchant_session['wholesale_discount'] : 100;

        if ($wholesale_discount>0 && $wholesale_discount<100) {
            $marketGoods['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
            $marketGoods['discount_totalMoney'] = round($totalMoney*$wholesale_discount*0.01, 2);
        }

        if (IS_POST) {
            $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : 0;
            $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : '';
            $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
            $userphone = isset($_POST['userphone']) ? htmlspecialchars(trim($_POST['userphone'])) : '';
            $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
            $desc = isset($_POST['desc']) ? htmlspecialchars(trim($_POST['desc'])) : '';
            $province_id = isset($_POST['province_id']) ? intval(trim($_POST['province_id'])) : '';
            $city_id = isset($_POST['city_id']) ? intval(trim($_POST['city_id'])) : '';
            $area_id = isset($_POST['area_id']) ? intval(trim($_POST['area_id'])) : '';
            if (empty($username)) {
                $this->error(L_('收货人不能为空'));
                exit;
            }
            if (empty($userphone)) {
                $this->error(L_('联系电话不能为空'));
                exit;
            }
            if (empty($address)) {
                $this->error(L_('收货地址不能为空'));
                exit;
            }
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info'] && $is_pick_in_store==0 && (empty($pick_msg) || !$pick_id)) {
                $this->error(L_('请选择正确的自提点，请刷新重试！'));
            }
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info'] && $is_pick_in_store==0 && !$is_pick) {
                $this->error(L_('当前商品无法自提，请刷新重试！'));
            } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==1 && !$express) {
                $this->error(L_('当前商品无法使用快递，请刷新重试！'));
            } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==2 && !$logistics) {
                $this->error(L_('当前商品无法使用物流，请刷新重试！'));
            }
            $marketOrder = array('mer_id' => $this->merchant_session['mer_id'], 'last_time' => time());
            $error_tips = '';
            $spec_price_arr = array();
            if ($marketGoods['spec_list']) {
                $buy_nums = $_POST['buy_nums'];
                
                $totalNum = 0;
                $totalPrice = 0;
                $spec_array = explode('#', $marketGoods['spec_value']);
                $target_spec_value_array = array();
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                    $numArray = explode(':', $row_array[1]);
                    if (intval($buy_nums[$i]) >= intval($numArray[2]) && (intval($buy_nums[$i]) <= intval($numArray[3]) || intval($numArray[3])==-1)) {
                        $totalNum += $buy_nums[$i];
                        if($group_id>0){
                            $totalPrice += $buy_nums[$i] * $numArray[5];
                        }else{
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                                $spec_price_arr[]=$numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                                $spec_price_arr[]=$numArray[1];
                            }
                        }
                        //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                        $numArray[3] = $buy_nums[$i];
                        $numArray[2] = 0;
                        $row_array[1] = implode(':', $numArray);
                        $target_spec_value_array[] = implode('|', $row_array);
                    } else {
                        $error_tips .= '您批发其中一种规格库存不足<br/>';
                    }
                }
                
                if ($totalNum <= 0) {
                    $error_tips .= '您至少要批发一种规格且满足对应的批发条件<br/>';
                }

                if ($marketGoods['express_template_id'] > 0 && $_POST['province_id']>0 && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $marketGoods['min_num'];
                if ($totalNum > $marketGoods['stock_num']) {
                    $error_tips .= '该商品库存不足<br/>';
                }
                if ($totalNum < $marketGoods['min_num']) {
                    $error_tips .= L_('一次至少要批发') . $marketGoods['min_num'] . $marketGoods['unit'] . '<br/>';
                }
                if($group_id>0){
                    $totalPrice = $totalNum * $marketGoods['group_team_price'];
                }else{
                    if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                        $totalPrice = $totalNum * $marketGoods['vip_wholesale_price'];
                        $marketGoods['o_price'] = $marketGoods['price'];
                        $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
                    } else {
                        $totalPrice = $totalNum * $marketGoods['price'];
                    }
                }
                if ($marketGoods['express_template_id'] > 0 && $_POST['province_id']>0 && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }

            if (empty($error_tips)) {
                $discount_info = null;
                foreach ($marketGoods['discount_info'] as $discount) {
                    if ($discount['num'] <= $totalNum) {
                        if ($discount_info) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } else {
                            $discount_info = $discount;
                        }
                    }
                }
                $actual_money = $totalPrice;
                if ($discount_info && $group_id<=0) {
                    $actual_money = round($totalPrice * $discount_info['discount'] * 0.1, 2);
                }

                // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
                $wholesale_discount = $this->merchant_session['wholesale_discount']>0 ? $this->merchant_session['wholesale_discount'] : 100;

                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $money = round($actual_money*$wholesale_discount*0.01, 2);
                } else {
                    $money = $actual_money;
                }
                

                // 针对系统 处理系统对应每个商家设置的 批发商品时候的优惠折扣 对应记录 应付金额和折扣
                $marketOrder['actual_money'] = $actual_money;
                $marketOrder['system_discount_info'] = $wholesale_discount;

                // 地址相关信息
                $marketOrder['province_id'] = $province_id;
                $marketOrder['city_id'] = $city_id;
                $marketOrder['area_id'] = $area_id;
                // 配送类型
                $marketOrder['is_pick_in_store'] = $is_pick_in_store;
                $marketOrder['pick_id'] = $pick_id;



                $marketOrder['money'] = $money;
                $marketOrder['total_price'] = $totalPrice;
                $marketOrder['num'] = $totalNum;


                if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                    $min_price = $marketGoods['vip_wholesale_price'];
                } else {
                    $min_price = $marketGoods['price'];
                }

                if ($spec_price_arr) {
                    $spec_price = implode(',', $spec_price_arr);
                    $marketOrder['spec_price'] = $spec_price;
                    $min_price = min($spec_price_arr);
                    if (!$min_price) {
                        $min_price = $marketGoods['price'];
                    }
                }

                if($group_id>0){
                    $marketOrder['price'] = $marketGoods['group_team_price'];
                }else{
                    if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                        $marketOrder['o_price'] = $marketOrder['price'];
                    }
                    $marketOrder['price'] = $min_price;
                }
                $marketOrder['sell_mer_id'] = $marketGoods['mer_id'];
                $marketOrder['sell_store_id'] = $marketGoods['store_id'];
                $marketOrder['status'] = 10;

                $marketOrder['goods_id'] = $marketGoods['goods_id'];
                $marketOrder['name'] = $marketGoods['name'];
                $marketOrder['number'] = $marketGoods['number'];
                $marketOrder['unit'] = $marketGoods['unit'];
                $marketOrder['image'] = $marketGoods['image'];
                $marketOrder['des'] = $marketGoods['des'];
                $marketOrder['is_properties'] = $marketGoods['is_properties'];
                $marketOrder['discount_info'] = $discount_info ? json_encode($discount_info) : '';
                
                $marketOrder['username'] = $username;
                $marketOrder['userphone'] = $userphone;
                $marketOrder['address'] = $address;
                $marketOrder['desc'] = $desc;
                $marketOrder['create_time'] = time();

                // 是不是享受的vip价格  0 不是  1 是
                $marketOrder['is_vip'] = $is_vip?1:0;

                // 配送费相关依据字段
                $marketOrder['freight_type'] = $marketGoods['freight_type']>0?$marketGoods['freight_type']:1;
                $marketOrder['goods_volume'] = $marketGoods['goods_volume']>0?$marketGoods['goods_volume']:0;
                $marketOrder['goods_weight'] = $marketGoods['goods_weight']>0?$marketGoods['goods_weight']:0;
                $marketOrder['express_template_id'] = $marketGoods['express_template_id']>0?$marketGoods['express_template_id']:0;
                $marketOrder['express_fee'] = $marketGoods['express_fee']>0?$marketGoods['express_fee']:0;
                
                $marketOrder['spec_value'] = $target_spec_value_array ? implode('#', $target_spec_value_array) : '';
                if($group_id>0){    //参团订单
                    $marketOrder['group_id']=$group_id;
                    if($team_id>0){
                        $marketOrder['team_id']=$team_id;
                        $marketOrder['group_type']=2;  //参团  
                    }else{
                        $marketOrder['group_type']=1;  //发起拼团
                    }
                }
                if ($marketOrderOld) {
                    if ($order_id = M('Market_order')->where(array('order_id' => $order_id, 'goods_id' => $goodsId, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->save($marketOrder)) {
                        $order_id = $marketOrderOld['order_id'];
                        //删除已有的属性与规格的数据
                        $where['order_id'] = $order_id;
                        M('Market_order_properties')->where($where)->delete();
                        $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                        foreach ($spec_list as $spec) {
                            M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                        }
                        M('Market_order_spec')->where($where)->delete();
                    }
                } else {
                    $order_id = M('Market_order')->add($marketOrder);
                }
        
                if ($order_id) {
                    if ($marketGoods['is_properties']) {
                        $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
                        foreach ($properties as $pro_data) {
                            $pro_data['order_id'] = $order_id;
                            M('Market_order_properties')->add($pro_data);
                        }
                    }
                    if ($marketGoods['spec_value']) {
                        $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
                        foreach ($spec_list as $spec) {
                            $spec['order_id'] = $order_id;
                            if (M('Market_order_spec')->add($spec)) {
                                $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    $spec_value['order_id'] = $order_id;
                                    M('Market_order_spec_value')->add($spec_value);
                                }
                            }
                        }
                    }
                    $this->success(L_('下单成功！'), U('pay', array('order_id' => $order_id)));
                } else {
                    $this->error(L_('下单失败！请重试！'));
                }
            } else {
                $this->error($error_tips);
            }
        } else {
            $this->assign('now_goods', $marketGoods);
            $this->assign('team_id', $team_id);
            $this->cartCount();
            $this->display();
        }
    }
    
    public function pay()
    {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
		if($this->config['nmgzhcs_appid']){
			$merchant['money'] = 0;			// 不使用余额
		}
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->find()) {
            $result=D('Market_goods')->check_group_act($marketOrder['group_id'],$marketOrder['team_id']);
            if($result['error']==1){
                $this->error($result['msg']);
            }
            if(!empty($marketOrder['image'])){
                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $marketOrder['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $marketOrder['pic_arr'][$key]['title'] = $value;
                    $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            
            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            $marketOrder['discount_info'] = json_decode($marketOrder['discount_info'], true);

            $m_area = M('Area');
            if ($marketOrder['province_id'] > 0) {
                $marketOrder['province'] = $m_area->where(array('area_id' => $marketOrder['province_id']))->getField('area_name');
            }
            if ($marketOrder['city_id'] > 0) {
                $marketOrder['city'] = $m_area->where(array('area_id' => $marketOrder['city_id']))->getField('area_name');
            }
            if ($marketOrder['area_id'] > 0) {
                $marketOrder['area'] = $m_area->where(array('area_id' => $marketOrder['area_id']))->getField('area_name');
            }


            $this->assign('now_goods', $marketOrder);
            $this->assign('merchant', $merchant);
        } else {
            $this->error(L_('订单信息错误'));
            exit();
        }
        $pay_method_all = D('Config')->get_pay_method(0,0);
        $pay_method = D('Market_order')->pay_method(true, false);
        if ($pay_method_all['weixin']) {
            $pay_method['weixin'] = $pay_method_all['weixin'];
        }
        $this->assign('pay_method', $pay_method);
        $this->assign('merchant', $merchant);


        if (IS_POST) {
            $nowtime = date("ymdHis");
            $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant_session['mer_id']);
		    $total_express_fee = $marketOrder['total_express_fee'] > 0 ? $marketOrder['total_express_fee'] : 0;
		    $_total = $marketOrder['money'] + $total_express_fee;
            $pay_type = $_POST['pay_type'] ? $_POST['pay_type'] : '';
            if ($pay_type == '' && $_total > $merchant['money']) {
                $money = floatval($_total - $merchant['money']);
                if (empty($money)||$money <0 ||!is_numeric($money)) {
                    $this->error(L_('请输入正确的充值金额'));
                }
                
                $data_mer_recharge_order['mer_id'] = $this->merchant_session['mer_id'];
                $data_mer_recharge_order['money'] = $money;
                $data_mer_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
                $data_mer_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
                $data_mer_recharge_order['label'] = 'web_marketorder_' . $order_id;
                if ($order_id = M('Merchant_recharge_order')->data($data_mer_recharge_order)->add()) {
                    redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'merrecharge')));
                } else {
                    $this->error_tips(L_('订单创建失败，请重试。'));
                }
            } elseif ($pay_type=='') {
                redirect(U('pay_success', array('order_id' => $order_id)));
            } elseif ($pay_type=='weixin') {
                $data_tmp['pay_type'] = $pay_type;
                $data_tmp['order_type'] = 'market';
                $data_tmp['order_id'] = $order_id;
                $data_tmp['orderid'] = $orderid;
                $data_tmp['addtime'] = $nowtime;
                if(!D('Tmp_orderid')->add($data_tmp)){
                    $this->error_tips(L_('更新失败，请联系管理员'));
                }
                $save_pay_id = D('Market_order')->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
                if(!$save_pay_id){
                    $this->error_tips(L_('更新失败，请联系管理员'));
                }
                $pay_class_name = ucfirst($_POST['pay_type']);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->setField('pay_type',$pay_type);
                $now_order = D('Market_order')->get_pay_order($this->merchant_session['mer_id'],$order_id,true);
                if(empty($now_order)){
                    $this->error_tips(L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;
                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant_session,0);
                $go_pay_param = $pay_class->pay();

                if(empty($go_pay_param['error'])){
                    if($pay_class_name == 'Weixin'){
                        header("Content-type: application/json");
                        $returnArr = array(
                            'status'=>1,
                            'info'=>$go_pay_param['qrcode'],
                            'orderid'=>$orderid,
                        );
                        echo json_encode($returnArr);
                        exit;
                    }else if(!empty($go_pay_param['url'])){
                        $this->assign('url',$go_pay_param['url']);
                    }else if(!empty($go_pay_param['form'])){
                        $this->assign('form',$go_pay_param['form']);
                    }else{
                        if(IS_AJAX){
                            $this->error(L_('调用支付发生错误，请重试。'));
                        }
                        else{
                            $this->error_tips(L_('调用支付发生错误，请重试。'));
                        }
                    }
                }else{
                    if(IS_AJAX){
                        $this->error($go_pay_param['msg']);
                    }
                    else{
                        $this->error_tips($go_pay_param['msg']);
                    }
                }
            } elseif ($pay_type=='offline') {
                // 线下支付未支付  修改状态为  6   线下状态支付 1
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->setField('pay_type',$pay_type);
                redirect(U('pay_success', array('order_id' => $order_id)));
            } else {
                $this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
            }
        } else {
            $this->display();
        }
    }
    
    public function pay_success()
    {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        // 购买总数
        $total_num = 0;
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->find()) {
            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            
            
            $databaseMarketGoods = D('Market_goods');
            $where = array('goods_id' => $marketOrder['goods_id']);
            $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
            if(empty($marketGoods)){
                
                //批发市场是没有这条数据的时候删除没有支付的订单所有的数据
                $where['order_id'] = $order_id;
                M('Market_order_properties')->where($where)->delete();
                $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                foreach ($spec_list as $spec) {
                    M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                }
                M('Market_order_spec')->where($where)->delete();
                
                $databaseMarketOrder->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->delete();
                
                $this->error(L_('该商品在批发市场上已下架，不能购买了'), U('market'));
                exit();
            }
            
            $marketGoodsChageData = array();
            if ($marketOrder['list']) {
                $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                
                $spec_array = explode('#', $marketGoods['spec_value']);
                
                foreach ($marketOrder['list'] as $index => $row) {
                    $target_spec_value_array = array();
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
                        if ($i == $index) {
                            $numArray = explode(':', $row_array[1]);
                            if ($numArray[3]!=-1) {
                                $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                            }
                            $row_array[1] = implode(':', $numArray);
                        }
                        $target_spec_value_array[] = implode('|', $row_array);
                    }
                    $spec_array = $target_spec_value_array;


                    if (isset($marketGoods['list'][$index])) {
                        if ($row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                            $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                            exit();
                        } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                            $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                            exit();
                        }
                    } else {
                        $this->error(L_('购买的商品已经没有这种规格了'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                        exit();
                    }
                    if ($row['stock_num'] > 0) {
                        $total_num += $row['stock_num'];
                    }
                }
                $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
            } elseif ($marketOrder['num'] > $marketGoods['stock_num']) {
                $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                exit();
            } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
                $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                exit();
            }
            
            $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
            $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];
            
            //自己减少余额
            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
            $total_express_fee = $marketOrder['total_express_fee'] > 0 ? $marketOrder['total_express_fee'] : 0;
            $_total = $marketOrder['money'] + $total_express_fee;
            $pay_type = $marketOrder['pay_type'] ? $marketOrder['pay_type'] : '';
            if ($pay_type == '' && $merchant['money'] >= $_total) {
                if($marketOrder['group_id']>0){
                    $desc=L_('购买批发市场的拼团商品【X1】扣除商家余额',$marketOrder['name']);
                }else{
                    $desc=L_('购买批发市场的商品【X1】扣除商家余额',$marketOrder['name']);
                }
                $returnData = D('Merchant_money_list')->use_money($this->merchant_session['mer_id'],$_total,'market',$desc,$marketOrder['order_id']);
            } elseif ($pay_type == '') {
                $this->error(L_('您的余额不足，请充值！'), U('pay', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                exit();
            } elseif ($pay_type == 'offline') {
                $set_data = array(
                    'offline_money' => $_total,
                    'offline_status' => 0,
                    'is_mobile_pay' => 0,
                    'last_time' => time()
                );
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 10))->data($set_data)->save();
            } else {
                $this->error(L_('您选择的支付方式不存在，请更新支付方式！'));
            }
            
            
            if ($returnData['error_code']) {
                $this->error(L_('支付失败！请重试！'), U('pay', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                exit();
            } elseif($marketOrder['group_id']<=0 && $pay_type != 'offline') {     //不是拼团的订单 且不是线下支付， 线下支付 实际付款确认后 分佣
                // 支付成功 查看是否存在推广者， 添加推广者分佣  添加开关控制
                if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                    $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $this->merchant_session['mer_id'])))->find();
                    if ($spread_merchant) {
                        $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                        // 计算分佣金额
                        $spread_money = $marketOrder['money'] * $merchant_recommend_spread_ratio;
                        $spread_money = round($spread_money, 2); // 分佣金额

                        $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$marketOrder['money'].$this->config['Currency_txt']));
                        $order_info['money'] = $spread_money;
                        $order_info['order_type'] = 'market_spread';
                        $order_info['mer_id'] = $spread_merchant['mer_id'];
                        $order_info['order_id'] = $marketOrder['order_id'];
                        $order_info['desc'] = $desc;
                        // 处理数量
                        if ($total_num > 0) {
                            $order_info['num'] = $total_num;
                        } else {
                            $order_info['num'] = $marketOrder['num'];
                        }
                        D('Merchant_money_list')->add_money($order_info);
                        // 同步记录到记录表格中
                        $order_info['spread_mer_id'] = $merchant['mer_id'];
                        $order_info['spread_mer_name'] = $merchant['name'];
                        $order_info['spread_time'] = $merchant['reg_time'];
                        $order_info['add_time'] = time();
                        M('Merchant_recommend_merchant_record')->data($order_info)->add();
                    }
                }
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant_session['mer_id']);
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('orderid' => $orderid));
            }elseif($marketOrder['group_id']>0){  //拼团成功
                D("Market_goods")->check_group_team($marketOrder,$marketGoods);
            }
            //修改订单状态
            if ($pay_type == 'offline') {
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('status' => 1, 'pay_type' => 'offline'));
            } else {
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('pay_time' => time(), 'status' => 1));
            }
            
            //更改批发市场中商品的库存
            $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);
            
            $sms_data = array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => 0, 'type' => 'market');
            $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
            if($marketOrder['group_id']<=0){
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
            }
            
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $merchant['phone'];;
            $sms_data['sendto'] = 'merchant';
            $sms_data['type'] = 'market';
            $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$sellmerchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
            Sms::sendSms($sms_data);
            
            $this->success(L_('支付成功'), U('buy_order'));
        } else {
            $this->error(L_('订单信息错误'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
            exit();
        }
    }
    
    public function order_detail()
    {
        $order_id = intval($_GET['order_id']);
        $type = $_GET['type'];
        if (!in_array($type, array('buy', 'sell'))) {
            $this->error(L_('信息来源不正确'));
        }

        
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        $orderList = array();
        if ($type == 'buy') {
            $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
            if ($totalData = D('Market_total')->where(array('id' => $fid, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
                $databaseMarketGoods = D('Market_order');
                $orderList = $databaseMarketOrder->where(array('mer_id' => $this->merchant_session['mer_id'], 'fid' => $fid))->select();
            } else {
                $where['mer_id'] = $this->merchant_session['mer_id'];
            }
        } else {
            $where['sell_mer_id'] = $this->merchant_session['mer_id'];
        }
        $userData = array();
        if ($orderList) {
            foreach ($orderList as &$marketOrder) {
                if(!empty($marketOrder['image'])){
                    $goods_image_class = new goods_image();
                    $tmp_pic_arr = explode(';', $marketOrder['image']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        $marketOrder['pic_arr'][$key]['title'] = $value;
                        $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                    }
                }
                
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $marketOrder['order_id']);
                $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                $marketOrder['discount_info'] = json_decode($marketOrder['discount_info'], true);
                $marketOrder['express_name'] = D('Express')->get_express($marketOrder['express_id']);
                $marketOrder['express_name'] = isset($marketOrder['express_name']['name']) ? $marketOrder['express_name']['name'] : '';
                
                if ($merchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find()) {
                    $marketOrder['merchant_name'] = $merchant['name'];
                    $marketOrder['merchant_phone'] = $merchant['phone'];
                    
                }
                if ($store = M('Merchant_store')->field('name, phone')->where(array('store_id' => $marketOrder['sell_store_id']))->find()) {
                    $marketOrder['store_name'] = $store['name'];
                    $marketOrder['store_phone'] = $store['phone'];
                }
                if (empty($userData)) {
                    $userData['express_id'] = $marketOrder['express_id'];
                    $userData['username'] = $marketOrder['username'];
                    $userData['userphone'] = $marketOrder['userphone'];
                    $userData['address'] = $marketOrder['address'];
                    $userData['desc'] = $marketOrder['desc'];
                    $userData['express_name'] = $marketOrder['express_name'];
                    $userData['express_number'] = $marketOrder['express_number'];
                    $userData['sell_note'] = $marketOrder['sell_note'];
                    $userData['create_time'] = $marketOrder['create_time'];
                    $userData['pay_time'] = $marketOrder['pay_time'];
                    $userData['send_time'] = $marketOrder['send_time'];
                    $userData['pull_time'] = $marketOrder['pull_time'];
                }
            }
            $this->assign('userData', $userData);
            $this->assign('orderList', $orderList);
            $this->display();
        } elseif ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if(!empty($marketOrder['image'])){
                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $marketOrder['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $marketOrder['pic_arr'][$key]['title'] = $value;
                    $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            
            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            $marketOrder['discount_info'] = json_decode($marketOrder['discount_info'], true);
            $marketOrder['express_name'] = D('Express')->get_express($marketOrder['express_id']);
            $marketOrder['express_name'] = isset($marketOrder['express_name']['name']) ? $marketOrder['express_name']['name'] : '';
            
            if ($type == 'buy') {
                if ($merchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find()) {
                    $marketOrder['merchant_name'] = $merchant['name'];
                    $marketOrder['merchant_phone'] = $merchant['phone'];
                    
                }
                if ($store = M('Merchant_store')->field('name, phone')->where(array('store_id' => $marketOrder['sell_store_id']))->find()) {
                    $marketOrder['store_name'] = $store['name'];
                    $marketOrder['store_phone'] = $store['phone'];
                }
            } else {
                if ($merchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['mer_id']))->find()) {
                    $marketOrder['merchant_name'] = $merchant['name'];
                    $marketOrder['merchant_phone'] = $merchant['phone'];
                    
                }
            }

            // 处理支付流水  Payment Flow
            if ($marketOrder['fid'] > 0) {
                $total_order = D('Market_total')->where(array('id' => $marketOrder['fid']))->find();
                if ($total_order && $total_order['orderid']) {
                    $userData['payment_flow'] = 'markets_' . $total_order['orderid'];
                }
                if ($total_order && $total_order['third_id']) {
                    $userData['payment_third_id'] = $total_order['third_id'];
                }
                if ($total_order && $total_order['pay_time']) {
                    $userData['pay_time'] = $total_order['pay_time'];
                }
                if ($total_order['status']==1) {
                    $marketOrder['paid'] = 1;
                } else {
                    $marketOrder['paid'] = 0;
                }
            } else {
                if ($marketOrder && $marketOrder['orderid']) {
                    $userData['payment_flow'] = 'markets_' . $marketOrder['orderid'];
                }
                if ($marketOrder && $marketOrder['third_id']) {
                    $userData['payment_third_id'] = $marketOrder['third_id'];
                }
                if ($marketOrder['status']>=1 && $marketOrder['status']<10) {
                    $marketOrder['paid'] = 1;
                } else {
                    $marketOrder['paid'] = 0;
                }
            }
            $pay_type_str = D('Pay')->get_pay_name($marketOrder['pay_type'], $marketOrder['is_mobile_pay'], $marketOrder['paid']);
            $userData['pay_type_str'] = $pay_type_str;

            $userData['username'] = $marketOrder['username'];
            $userData['userphone'] = $marketOrder['userphone'];
            $userData['address'] = $marketOrder['address'];
            $userData['desc'] = $marketOrder['desc'];
	        $userData['express_id'] = $marketOrder['express_id'];
            $userData['express_name'] = $marketOrder['express_name'];
            $userData['express_number'] = $marketOrder['express_number'];
            $userData['sell_note'] = $marketOrder['sell_note'];
            $userData['create_time'] = $marketOrder['create_time'];
            $userData['pay_time'] = $marketOrder['pay_time'];
            $userData['send_time'] = $marketOrder['send_time'];
            $userData['pull_time'] = $marketOrder['pull_time'];
            $this->assign('userData', $userData);
            $this->assign('orderList', array($marketOrder));
            $this->display();
        } else {
            $this->error(L_('错误的订单信息'));
        }
    }

    // 确认线下收钱
    public function offline_get_money() {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $this->merchant_session['mer_id']))->find()) {
            if ($marketOrder['pay_type'] == 'offline' && $marketOrder['offline_status'] == 1) {
                $this->error(L_('此单线下已经确认收钱'));
                exit;
            } elseif ($marketOrder['pay_type'] != 'offline') {
                $this->error(L_('此单非线下支付，无法确认收钱'));
                exit;
            }

            $set_data = array(
                'offline_status' => 1,
                'last_time' => time()
            );
            $save_id = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $this->merchant_session['mer_id']))->data($set_data)->save();
            if ($save_id) {
                if($marketOrder['group_id']<=0) {     //不是拼团的订单 且是线下支付， 线下支付 实际付款确认后 开始分佣
                    $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
                    // 支付成功 查看是否存在推广者， 添加推广者分佣  添加开关控制
                    if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                        $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $this->merchant_session['mer_id'])))->find();
                        if ($spread_merchant) {
                            $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                            // 计算分佣金额
                            $spread_money = $marketOrder['money'] * $merchant_recommend_spread_ratio;
                            $spread_money = round($spread_money, 2); // 分佣金额

                            $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$marketOrder['money'].$this->config['Currency_txt']));
                            $order_info['money'] = $spread_money;
                            $order_info['order_type'] = 'market_spread';
                            $order_info['mer_id'] = $spread_merchant['mer_id'];
                            $order_info['order_id'] = $marketOrder['order_id'];
                            $order_info['desc'] = $desc;
                            // 处理数量
                            $order_info['num'] = $marketOrder['num'];
                            D('Merchant_money_list')->add_money($order_info);
                            // 同步记录到记录表格中
                            $order_info['spread_mer_id'] = $merchant['mer_id'];
                            $order_info['spread_mer_name'] = $merchant['name'];
                            $order_info['spread_time'] = $merchant['reg_time'];
                            $order_info['add_time'] = time();
                            M('Merchant_recommend_merchant_record')->data($order_info)->add();
                        }
                    }
                }

                // 减少库存
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                $databaseMarketGoods = D('Market_goods');
                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';

                    $spec_array = explode('#', $marketGoods['spec_value']);

                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3]!=-1) {
                                    $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;


                        if (isset($marketGoods['list'][$index])) {
                            if ($row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                                $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                                exit();
                            } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                                $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                                exit();
                            }
                        } else {
                            $this->error(L_('购买的商品已经没有这种规格了'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                            exit();
                        }
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                } elseif ($marketOrder['num'] > $marketGoods['stock_num']) {
                    $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                    exit();
                } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
                    $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                    exit();
                }

                $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];


                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);

                $this->success(L_('确认成功'));
            } else {
                $this->error(L_('确认收钱失败，请重试~'));
            }
        } else {
            $this->error(L_('错误的订单信息'));
        }
    }


    public function market_pick()
    {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($marketOrder['mer_id'] != $this->merchant_session['mer_id'] && $marketOrder['sell_mer_id'] != $this->merchant_session['mer_id']){
                $this->error(L_('您不能进行此操作！'));
            } elseif ($marketOrder['status'] != 1 || $marketOrder['is_pick_in_store'] != 0) {
                $this->error(L_('此单当前不能自提'));
            }
            if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time(), 'pick_mer_id' => $this->merchant['mer_id']))) {
                //对方增加余额
                $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                $money = floatval($marketOrder['money']);
                $total_money = $money + $total_express_fee;
                $data = array();
                $data['order_type'] = 'market';
                $data['desc'] = L_('批发市场的商品【').$marketOrder['name'].L_('】卖出时增加商家余额');
                $data['mer_id'] = $marketOrder['sell_mer_id'];
                $data['money'] = $marketOrder['money'];
                $data['order_id'] = $marketOrder['order_id'];
                $data['total_money'] = $total_money;
                // D('Merchant_money_list')->add_money($data);
                D('SystemBill')->bill_method(0,$data);


                $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['content'] = L_('X1的X2共X3对方已经自提，订单号：X4',array('X1'=>$this->merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);


                $this->success(L_('自提成功'));
            } else {
                $this->error(L_('自提失败，稍后重试~'));
            }
        } else {
            $this->error(L_('错误的订单信息'));
        }
    }

    //发货
    public function push()
    {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $this->merchant_session['mer_id']))->find()) {
            if ($marketOrder['status'] != 2 && $marketOrder['status'] != 1) {
                $this->error(L_('此单不能发货'));
                exit;
            }
            if($marketOrder['group_id']>0){
                $team_status=M('Market_group_team')->where(array('id'=>$marketOrder['team_id']))->getfield('status');
                if($team_status==0){
                    $this->error(L_('该拼团订单尚未成团不能发货！'));
                }elseif($team_status==2){
                    $this->error(L_('该拼团订单拼团失败，不能发货！'));
                }
            }
            if (IS_POST) {
                $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
                $express_number = isset($_POST['express_number']) ? htmlspecialchars(trim($_POST['express_number'])) : 0;
                $sell_note = isset($_POST['sell_note']) ? htmlspecialchars(trim($_POST['sell_note'])) : '';
                $express = D('Express')->get_express($express_id);
                if (empty($express)) {
                    $this->error(L_('请选择正确的快递公司'));
                    exit;
                }
                if (empty($express_number)) {
                    $this->error(L_('请选择填写快递单号'));
                    exit;
                }
                $data['express_id'] = $express_id;
                $data['express_number'] = $express_number;
                $data['sell_note'] = $sell_note;
                $data['send_time'] = time();
                $data['status'] = 2;
                if ($databaseMarketOrder->where(array('order_id' => $order_id, 'sell_mer_id' => $this->merchant_session['mer_id']))->save($data)) {
                    // 发货成功 如果是虚拟批发 同步状态到虚拟批发上
                    if ($marketOrder && $marketOrder['type']==1 && $marketOrder['integral_mall_goods_order_id']>0) {
                        $mall_order_id = $marketOrder['integral_mall_goods_order_id'];
                        $data['express_id'] = $express_id;
                        $data['express_number'] = $express_number;
                        $data['send_note'] = $sell_note;
                        $data['send_time'] = time();
                        $data['status'] = 2;
                        $order_info = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->find();
                        if (M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->save($data)) {
                            $sms_data = array('mer_id' => $order_info['mer_id'], 'store_id' => 0, 'type' => 'integral_mall');
                            $now_user = M('User')->where(array('uid' => $order_info['uid']))->field('phone')->find();
                            $sms_data['uid'] = 0;
                            $sms_data['mobile'] = $now_user['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['content'] = L_('您在X1兑换了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$order_info['goods_name'],'X3'=>$order_info['num'] . '份','X4'=>$order_info['orderid']));
                            Sms::sendSms($sms_data);
                            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_info['order_id'], 'status' => 2, 'from_uid' => $order_info['mer_id'], 'from_type' => 2, 'note' => '商户发货'));
                        } else {
                            $databaseMarketOrder->where(array('order_id' => $order_id, 'sell_mer_id' => $this->merchant_session['mer_id']))->save(array('status' => 1));
                            $this->error(L_('发货失败'));
                        }
                    } else {
                        $sms_data = array('mer_id' => $marketOrder['mer_id'], 'store_id' => 0, 'type' => 'market');

                        $buymerchant = M('Merchant')->where(array('mer_id' => $marketOrder['mer_id']))->find();
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $buymerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                        Sms::sendSms($sms_data);
                    }
                    $this->success(L_('发货成功'));
                } else {
                    $this->error(L_('发货失败'));
                }
            } else {
                if(!empty($marketOrder['image'])){
                    $goods_image_class = new goods_image();
                    $tmp_pic_arr = explode(';', $marketOrder['image']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        $marketOrder['pic_arr'][$key]['title'] = $value;
                        $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                    }
                }
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                $marketOrder['discount_info'] = json_decode($marketOrder['discount_info'], true);
                $m_area = M('Area');
                if ($marketOrder['province_id'] > 0) {
                    $marketOrder['province'] = $m_area->where(array('area_id' => $marketOrder['province_id']))->getField('area_name');
                }
                if ($marketOrder['city_id'] > 0) {
                    $marketOrder['city'] = $m_area->where(array('area_id' => $marketOrder['city_id']))->getField('area_name');
                }
                if ($marketOrder['area_id'] > 0) {
                    $marketOrder['area'] = $m_area->where(array('area_id' => $marketOrder['area_id']))->getField('area_name');
                }
                $this->assign('now_goods', $marketOrder);
                $this->assign('express_list', D('Express')->get_express_list());
                $this->display();
            }
        } else {
            $this->error(L_('错误的订单信息'));
        }
    }

    //批量发货
    public function batch_push()
    {
        $order_id_str = $_GET['order_list'];
        $order_id_arr = explode(",", $order_id_str);
        if (empty($order_id_arr) || empty($order_id_str)) {
            $this->error(L_('请选择可发货的单号！'));
        }
        $databaseMarketOrder = D('Market_order');

        // 查询选择的可发货的单号
        $market_order_list = $databaseMarketOrder->field(true)->where(array('order_id' => array('in', $order_id_arr), 'status'=>array('in', array(1,2)), 'sell_mer_id' => $this->merchant_session['mer_id']))->find();
        if (empty($order_id_arr)) {
            $this->error(L_('选择的单号均不能进行发货，请选择可发货的单号！'));
        }
        if (IS_POST) {
            $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
            $express_number = isset($_POST['express_number']) ? htmlspecialchars(trim($_POST['express_number'])) : 0;
            $sell_note = isset($_POST['sell_note']) ? htmlspecialchars(trim($_POST['sell_note'])) : '';
            $express = D('Express')->get_express($express_id);
            if (empty($express)) {
                $this->error(L_('请选择正确的快递公司'));
                exit;
            }
            if (empty($express_number)) {
                $this->error(L_('请选择填写快递单号'));
                exit;
            }
            $data['express_id'] = $express_id;
            $data['express_number'] = $express_number;
            $data['sell_note'] = $sell_note;
            $data['send_time'] = time();
            $data['status'] = 2;
            $save_info=$databaseMarketOrder->where(array('order_id' => array('in', $order_id_arr), 'status'=>array('in', array(1,2)), 'sell_mer_id' => $this->merchant_session['mer_id']))->data($data)->save();
            if ($save_info) {
				$sms_data = array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => 0, 'type' => 'market');
                $m_merchant = M('Merchant');
                foreach ($market_order_list as $order) {
                    if ($order && $order['type']==1 && $order['integral_mall_goods_order_id']>0) {
                        $mall_order_id = $order['integral_mall_goods_order_id'];
                        $data = [];
                        $data['express_id'] = $express_id;
                        $data['express_number'] = $express_number;
                        $data['send_note'] = $sell_note;
                        $data['send_time'] = time();
                        $data['status'] = 2;
                        $order_info = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->find();
                        $save_msg = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->save($data);
                        if ($save_msg!==false) {
                            $sms_data = array('mer_id' => $order_info['mer_id'], 'store_id' => 0, 'type' => 'integral_mall');
                            $now_user = M('User')->where(array('uid' => $order_info['uid']))->field('phone')->find();
                            $sms_data['uid'] = 0;
                            $sms_data['mobile'] = $now_user['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['content'] = L_('您在X1兑换了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$order_info['goods_name'],'X3'=>$order_info['num'] . '份','X4'=>$order_info['orderid']));
                            Sms::sendSms($sms_data);
                            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_info['order_id'], 'status' => 2, 'from_uid' => $order_info['mer_id'], 'from_type' => 2, 'note' => '商户发货'));
                        } else {
                            $databaseMarketOrder->where(array('order_id' => $order['order_id'], 'sell_mer_id' => $this->merchant_session['mer_id']))->save(array('status' => 1));
                            $this->error(L_('发货失败'));
                        }
                    } else {
                        $buymerchant = $m_merchant->where(array('mer_id' => $order['mer_id']))->find();
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $buymerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$order['name'],'X3'=>$order['num'] . $order['unit'],'X4'=>$order['order_id']));
                        Sms::sendSms($sms_data);
                    }
                }
                $this->success(L_('发货成功'));
            } else {
                $this->error(L_('发货失败'));
            }
        } else {
            $goods_image_class = new goods_image();
            foreach ($market_order_list as &$order) {
                if(!empty($order['image'])){
                    $tmp_pic_arr = explode(';', $order['image']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        $order['pic_arr'][$key]['title'] = $value;
                        $order['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                    }
                }
                $return = $databaseMarketOrder->format_spec_value($order['spec_value'], $order['goods_id'], $order['order_id']);
                $order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $order['list'] = isset($return['list']) ? $return['list'] : '';
                $order['discount_info'] = json_decode($order['discount_info'], true);
            }
            $this->assign('order_id_str', $order_id_str);
            $this->assign('now_goods_list', $market_order_list);
            $this->assign('express_list', D('Express')->get_express_list());
            $this->display();
        }
    }
    
    //收货
    public function pull()
    {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($marketOrder['status'] != 2) {
                exit(json_encode(array('error_code' => true, 'msg' => L_('此单暂时不能收货'))));
            } else {
                if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))) {

                    if ($marketOrder && $marketOrder['type']==1 && $marketOrder['integral_mall_goods_order_id']>0) {
                        $mall_order_id = $marketOrder['integral_mall_goods_order_id'];
                        $data = array('status' => 3);
                        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
                        $data['last_time'] = $_SERVER['REQUEST_TIME'];
                        $order_info = M('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->find();
                        if (D('Merchant_integral_mall_goods_order')->where(array('order_id' => $mall_order_id))->save($data)) {

                            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 3, 'from_uid' => $this->user_session['uid'], 'note' => '用户确认收货'));

                            // 用户确认收货-计算给予商家结算
                            D('Merchant_integral_mall_goods_order')->order_settlement($order_info);
                        }
                        $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $sellmerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$this->merchant_session['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                        Sms::sendSms($sms_data);
                    } else {

                        //对方增加余额
                        $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                        $money = floatval($marketOrder['money']);
                        $total_money = $money + $total_express_fee;
                        $data = array();
                        $data['order_type'] = 'market';
                        $data['desc'] = L_('批发市场的商品【').$marketOrder['name'].L_('】卖出时增加商家余额');
                        $data['mer_id'] = $marketOrder['sell_mer_id'];
                        $data['money'] = $marketOrder['money'];
                        $data['order_id'] = $order_id;
                        $data['total_money'] = $total_money;
                        // D('Merchant_money_list')->add_money($data);
                        D('SystemBill')->bill_method(0,$data);

                        $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                        $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $sellmerchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$this->merchant_session['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                        Sms::sendSms($sms_data);
                    }
                    
                    
                    exit(json_encode(array('error_code' => false, 'msg' => 'ok')));
                } else {
                    exit(json_encode(array('error_code' => true, 'msg' => L_('收货失败，稍后重试'))));
                }
            }
        } else {
            exit(json_encode(array('error_code' => true, 'msg' => L_('订单信息错误'))));
        }
    }
    
    public function add_to_store()
    {
        $now_merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->field('is_market_goods_upper_store')->find();
        $is_market_goods_upper_store = $now_merchant['is_market_goods_upper_store']?$now_merchant['is_market_goods_upper_store']:0;
        if ($is_market_goods_upper_store == 1) {
            $this->error(L_('当前您不能上架商品，如需上架请联系管理员！'));
        }

        $order_id = intval($_GET['order_id']);
        $store_id = intval($_GET['store_id']);
		$now_store = $this->check_store($store_id);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 3);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($tGoods = M('Shop_goods')->field(true)->where(array('original_goods_id' => $marketOrder['goods_id'], 'store_id' => $store_id))->find()) {
                $this->error(L_('该商品已上架，请不要重复上架'));
                exit;
            }
            if(!empty($marketOrder['image'])){
                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $marketOrder['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $marketOrder['pic_arr'][$key]['title'] = $value;
                    $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $marketOrder['cost_price'] = $marketOrder['price'];
            $marketOrder['stock_num'] = $marketOrder['num'];
            
            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);

            $marketOrder['json'] = isset($return['json']) ? json_encode($return['json']) : '';
            $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            if (IS_POST) {
                $error_tips = '';
                if (empty($_POST['name'])) {
                    $error_tips .= L_('商品名称必填！').'<br/>';
                }
                if (empty($_POST['unit'])) {
                    $error_tips .= L_('商品单位必填！').'<br/>';
                }
                if (empty($_POST['price'])&&!$this->config['open_extra_price']) {
                    $error_tips .= L_('商品价格必填！').'<br/>';
                }
                if (empty($_POST['pic'])) {
                    $error_tips .= L_('请至少上传一张照片！').'<br/>';
                }

                $_POST['des'] = fulltext_filter($_POST['des']);
            
                $img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
                $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
                foreach($_POST['pic'] as $kp => $vp){
                    $tmp_vp = explode(',', $vp);
                    if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
                    $_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
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
                	
                $sort_id = 0;
                for ($i = 1; $i <= self::GOODS_SORT_LEVEL; $i++) {
                    if (isset($_POST['sort_id_' . $i]) && intval($_POST['sort_id_' . $i])) {
                        $sort_id = intval($_POST['sort_id_' . $i]);
                        unset($_POST['sort_id_' . $i]);
                    }
                }
                if (empty($sort_id)) {
                    $error_tips .= L_('请选择分类').'<br/>';
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
                	
                $_POST['seckill_open_time'] = strtotime($_POST['seckill_open_time'] . ":00");
                $_POST['seckill_close_time'] = strtotime($_POST['seckill_close_time'] . ":00");
            
                if (empty($error_tips)) {
                    $_POST['goods_id'] = $marketOrder['goods_id'];
                    $_POST['sort_id'] = $sort_id;
                    $_POST['store_id'] = $now_store['store_id'];
                    $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
//                     echo '<pre/>';
//                     print_r($_POST);die;
                    if ($goods_id = D('Shop_goods')->savePostForm($_POST, $now_store['store_id'])) {
                        D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');
                        $this->success(L_('保存成功！'), U('Shop/goods_list', array('sort_id' => $sort_id)));
                        die;
                        $ok_tips = L_('保存成功！');
                    } else {
                        $this->error(L_('保存失败！请重试！'));
                    }
                } else {
                    $return = $this->format_data($_POST);
                    $_POST['json'] = isset($return['json']) ? json_encode($return['json']) : '';
                    $_POST['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
                    $_POST['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                    $_POST['list'] = isset($return['list']) ? $return['list'] : '';
                    $this->assign('now_goods', $_POST);
                }
                $this->assign('ok_tips', $ok_tips);
                $this->assign('error_tips', $error_tips);
            }
            
            $print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
            foreach ($print_list as &$l) {
                if ($l['is_main']) {
                    $l['name'] .= '(主打印机)';
                } else {
                    $l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
                }
            }
            $this->assign('print_list', $print_list);
            $category_list = D('Goods_category')->get_list();
            $this->assign('category_list', json_encode($category_list));
            
            $sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false);
            $this->assign('sort_list', json_encode($sort_list));
            $ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
            $this->assign('select_ids', json_encode($ids));
            
            $this->assign('now_goods', $marketOrder);
//             $this->assign('now_sort', $now_sort);
            $this->assign('now_store', $now_store);
            $this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
            
            $this->display();
        } else {
            $this->error(L_('订单信息有误，暂不能发布到店铺'));
        }
    }
    
    public function select_store()
    {
        $now_merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->field('is_market_goods_upper_store')->find();
        $is_market_goods_upper_store = $now_merchant['is_market_goods_upper_store']?$now_merchant['is_market_goods_upper_store']:0;
        if ($is_market_goods_upper_store == 1) {
            $this->error(L_('当前您不能上架商品，如需上架请联系管理员！'));
        }
        $order_id = intval($_GET['order_id']);

        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id'], 'status' => 3);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            $sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON sh.store_id=s.store_id WHERE s.have_shop=1 AND s.status=1 AND s.mer_id={$this->merchant_session['mer_id']}";
            $res = D()->query($sql);
            $storeIds = array();
            $storeList = array();
            foreach ($res as $r) {
                $storeIds[] = $r['store_id'];
                $storeList[$r['store_id']] = $r;
            }
            
            $goods_list = M('Shop_goods')->field(true)->where(array('original_goods_id' => $marketOrder['goods_id'], 'store_id' => array('in', $storeIds)))->select();
            $goodsList = array();
            foreach ($goods_list as $goods) {
                unset($storeList[$goods['store_id']]);
            }
            $this->assign('stores', $storeList);
            $this->display();
        } else {
            $this->error(L_('订单信息有误，暂不能发布到店铺'));
        }
    }
    
    /* 检测店铺存在，并检测是不是归属于商家 */
    protected function check_store($store_id)
    {
        if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $store_id) {
            $this->error(L_('您没有这个权限'));
        }
         
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $store_id;
        $condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
        $now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
        if (empty($now_store)) {
            $this->error(L_('店铺不存在！'));
        } else {
            //return $now_store;
            if ($now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find()) {
                if (!empty($now_shop['background'])) {
                    $image_tmp = explode(',', $now_shop['background']);
                    $now_shop['background_image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
                }
                return array_merge($now_store, $now_shop);
            }
            return $now_store;
            $now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find();
            return array_merge($now_store, $now_shop);
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
            $index = $pre = '';
            $tdata = array();
            foreach ($array as $irow) {
                $k = $irow[0];
                $ki = $irow[1];
                $r = $data['spec_val_id'][$irow[0]][$irow[1]];
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
            $json[$index]['numbers[]'] = $data['numbers'][$fi];
        }
        return array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json);
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
    
    public function getTotalPrice()
    {
        
        $goodsId = intval($_GET['goods_id']);
        $order_id = intval($_GET['order_id']);
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            exit(json_encode(array('error' => true, 'msg' => L_('商品不存在！'))));
        }
        
        $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
        $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
        $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);
        $marketGoods['username'] = $this->merchant_session['name'];
        $marketGoods['userphone'] = $this->merchant_session['phone'];
        $marketGoods['address'] = '';
        $marketGoods['buy_num'] = $marketGoods['min_num'];

        $is_vip = false;
        if ($this->merchant_session['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant_session['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        $data = array(
            'error' => false
        );
        /*是否是拼团商品*/
        $is_group=I('is_group',0,'intval');
        $group_totalPrice=0;
        if (IS_POST) {
            $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : 0;
            if ($marketGoods['spec_list']) {
                $buy_nums = $_POST['buy_nums'];
                
                $totalNum = 0;
                $totalPrice = 0;
                $spec_array = explode('#', $marketGoods['spec_value']);
                $target_spec_value_array = array();
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                    $numArray = explode(':', $row_array[1]);
                    if (intval($buy_nums[$i]) >= intval($numArray[2]) && (intval($buy_nums[$i]) <= intval($numArray[3]) || intval($numArray[3])==-1)) {
                        $totalNum += $buy_nums[$i];
                        if($is_group==1){
                            $group_totalPrice += $buy_nums[$i] * $numArray[5];
                        }
                        if ($is_vip && $numArray[7]>0) {
                            $totalPrice += $buy_nums[$i] * $numArray[7];
                        } else {
                            $totalPrice += $buy_nums[$i] * $numArray[1];
                        }
                    } else {
                        $msg = array(
                            'error' => true,
                            'error_msg' => '抱歉，库存不足'
                        );
                        exit(json_encode($msg));
                    }
                }
                if ($totalNum <= 0) {
                    $totalPrice = 0;
                }
                if ($_POST['province_id']>0 && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $data['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $marketGoods['min_num'];
                if ($totalNum > $marketGoods['stock_num'] || $totalNum < $marketGoods['min_num']) {
                    $totalPrice = 0;
                } else {
                    if($is_group==1){
                        $group_totalPrice = $totalNum * $marketGoods['group_team_price'];
                    }
                    $totalPrice = $totalNum * $marketGoods['price'];
                }
                if ($_POST['province_id']>0  && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $data['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }
            
            $discount_info = null;
            foreach ($marketGoods['discount_info'] as $discount) {
                if ($discount['num'] <= $totalNum) {
                    if ($discount_info) {
                        if ($discount_info['discount'] > $discount['discount']) {
                            $discount_info = $discount;
                        }
                    } else {
                        $discount_info = $discount;
                    }
                }
            }
            $money = $totalPrice;
            if ($discount_info) {
                $money = $totalPrice * $discount_info['discount'] * 0.1;
            }

            $data['totalMoney'] = round($money, 2);
            //拼团总价
            $data['group_totalMoney']=$group_totalPrice;

            // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
            $wholesale_discount = $this->merchant_session['wholesale_discount']>0 ? $this->merchant_session['wholesale_discount'] : 100;

            if ($wholesale_discount>0 && $wholesale_discount<100) {
                $data['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
                $data['discount_totalMoney'] = round($money*$wholesale_discount*0.01, 2);
            }
            exit(json_encode($data));
        }
    }
    
    public function changeStatus()
    {
        $goodsId = intval($_POST['id']);
        $where = array('goods_id' => $goodsId, 'mer_id' => $this->merchant_session['mer_id']);
        $status = $_POST['type'] == 'open' ? '1' : '3';
        if (M('Market_goods')->where($where)->save(array('status' => $status))) {
            if (3==$status) {
                // 如果商品下架，对应的虚拟批发也下架
                $model = D('Merchant_integral_mall_goods');
                $where_integral = array('virtual_wholesale_mer_id' => $this->merchant_session['mer_id']);
                $where_integral['_string'] = "virtual_wholesale_goods_id = {$goodsId} AND market_goods_id = {$goodsId}";
                $model->where($where_integral)->save(array('status' => 3));
            } elseif (1==$status) {
                // 如果商品状态恢复，对应的下架虚拟批发也恢复
                $model = D('Merchant_integral_mall_goods');
                $where_integral = array('virtual_wholesale_mer_id' => $this->merchant_session['mer_id'], 'status'=>3);
                $where_integral['_string'] = "virtual_wholesale_goods_id = {$goodsId} AND market_goods_id = {$goodsId}";
                $model->where($where_integral)->save(array('status' => 1));
            }
            exit('1');
        } else {
            exit;
        }
    }
    
    public function cancel()
    {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            
            
            if ($marketOrder['status'] != 10 && $marketOrder['status'] != 1) {
                $this->error(L_('商品只有在未发货之前能删除订单'));
                exit();
            }
            
            //已支付的订单要回滚库存和回退余额
            if ($marketOrder['status'] == 1) {
                $databaseMarketGoods = D('Market_goods');
                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                if(empty($marketGoods)){
                    $this->error(L_('商品信息错误'), U('market'));
                    exit();
                }
                
                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3] != -1) {
                                    $numArray[3] = max(0, ($numArray[3] + $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                }
                
                $marketGoodsChageData['stock_num'] = $marketGoods['stock_num'] + $marketOrder['num'];
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] - $marketOrder['num'];
                
                
                $data = array();
                $data['order_type'] = 'marketcancel';
                $data['desc'] = L_('取消在批发市场已购的商品【X1】时返还的金额',$marketOrder['name']);
                $data['mer_id'] = $this->merchant_session['mer_id'];
                $data['money'] = $marketOrder['money'];
                $data['order_id'] = $marketOrder['order_id'];
                $data['total_money'] = $marketOrder['money'];
                D('SystemBill')->bill_method(0,$data);
                
            }
            
            D('Market_order_properties')->where(array('order_id' => $order_id))->delete();
            D('Market_order_spec')->where(array('order_id' => $order_id))->delete();
            D('Market_order_spec_value')->where(array('order_id' => $order_id))->delete();
            
            //更新父订单的总价
            if ($marketOrder['fid'] && $marketOrder['status'] == 10) {
                if ($totalData = D('Market_total')->field(true)->where(array('id' => $marketOrder['fid']))->find()) {
                    D('Market_total')->where(array('id' => $marketOrder['fid']))->save(array('money' => $totalData['money'] - $marketOrder['money']));
                }
            }
            $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']))->delete();
            
            //更改批发市场中商品的库存
            D('Market_goods')->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);
            
            $this->success(L_('删除成功'));
        } else {
            $this->error(L_('订单信息错误'));
            exit();
        }
    }
    
    public function addCart()
    {
        if (IS_POST) {
            $goodsId = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : 1;
            $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : '';
            $databaseMarketGoods = D('Market_goods');
            $where = array('goods_id' => $goodsId);
            $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
            if(empty($marketGoods)){
                $this->error(L_('商品不存在！'));
            }
            if ($marketGoods['status'] != 1) {
                $this->error(L_('商品已下架！'));
            }
            $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
            $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
            $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);

            $is_vip = false;
            $is_pick = false;
            $express = false;
            $logistics = false;
            // 如果允许自提获取自提点信息
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
                $pick_info = explode(',', $marketGoods['pick_info']);
                $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
                $pick_address = array();
                $pick_msg = array();
                foreach ($pick_list as $val) {
                    if (in_array($val['pick_addr_id'], $pick_info)) {
                        unset($val['pwd']);
                        $pick_address[] = $val;
                        if ($pick_id == $val['pick_addr_id']) {
                            $pick_msg = $val;
                        }
                    }
                }
                if (empty($pick_address)) {
                    $is_pick = false;
                } else {
                    $is_pick = true;
                }
                $this->assign('pick_address', $pick_address);
                $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
                if (IS_POST && $pick_msg) {
                    $_POST['username'] = $merchant['name'];
                    $_POST['userphone'] = $pick_msg['phone'];
                    $_POST['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                    if (!empty($pick_msg['province'])) {
                        $_POST['province_id'] = $pick_msg['province'];
                    }
                    if (!empty($pick_msg['city'])) {
                        $_POST['city_id'] = $pick_msg['city'];
                    }
                    if (!empty($pick_msg['area'])) {
                        $_POST['area_id'] = $pick_msg['area'];
                    }
                }
            }
            // 获取一下对应运费模板消息
            if ($marketGoods['express_template_id'] > 0) {
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $marketGoods['express_template_id']))->select();
                foreach ($template_msgs as $val) {
                    if ($val['type']==1) {
                        $express = true;
                    } elseif ($val['type']==2) {
                        $logistics = true;
                    }
                }
            }
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info'] && $is_pick_in_store==0 && !$is_pick) {
                $this->error(L_('当前商品无法自提，请刷新重试！'));
            } elseif ($marketGoods['express_template_id']>0 && $is_pick_in_store==1 && !$express) {
                $this->error(L_('当前商品无法使用快递，请刷新重试！'));
            } elseif ($marketGoods['express_template_id']>0 && $is_pick_in_store==2 && !$logistics) {
                $this->error(L_('当前商品无法使用物流，请刷新重试！'));
            }
            if ($this->merchant_session['type_id']>0) {
                $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant_session['type_id']))->find();
                $is_vip = (1==$merchant_type['is_vip'])?true:false;
            }
            $totalNum = 0;
            $totalPrice = 0;
            $specArr = array();
            if ($marketCartOld = M('Market_cart')->field(true)->where(array('goods_id' => $goodsId, 'mer_id' => $this->merchant_session['mer_id'], 'is_pick_in_store' => $is_pick_in_store))->find()) {
                if ($marketCartOld['spec_value'] && $marketGoods['list']) {
                    $spec_array = explode('#', $marketCartOld['spec_value']);
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
//                         if (isset($marketGoods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $specArr[$i] = explode(':', $row_array[1]);
//                             $marketGoods['list'][$i]['buy_num'] = $tArray[3];
//                             $totalCount += $tArray[3];
//                             $totalMoney += $tArray[1] * $tArray[3];
//                         }
                    }
                } else {
                    $totalCount = $marketCartOld['num'];
//                     $totalMoney = $marketCartOld['num'] * $marketCartOld['price'];
                }
            }
            $error_tips = '';
            $spec_price_arr = array();
            $marketCart = array('mer_id' => $this->merchant_session['mer_id'], 'last_time' => time());
            $marketCart['is_pick_in_store'] = $is_pick_in_store;
            $marketCart['pick_id'] = $pick_id;
            if ($marketGoods['spec_list']) {
                $buy_nums = $_POST['buy_nums'];
                $spec_array = explode('#', $marketGoods['spec_value']);
                $target_spec_value_array = array();
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                    $ii = str_replace(':', '_', $row_array[0]);
                    if (isset($specArr[$ii]) && $specArr[$ii]) {
                        $specArr[$ii][3] += $buy_nums[$i];
                        $totalNum += $specArr[$ii][3];
                        if ($is_vip && $specArr[$ii][7]>0) {
                            $totalPrice += $specArr[$ii][3] * $specArr[$ii][7];
                            $spec_price_arr[] = $specArr[$ii][7];
                        } else {
                            $totalPrice += $specArr[$ii][3] * $specArr[$ii][1];
                            $spec_price_arr[] = $specArr[$ii][1];
                        }
                        $row_array[1] = implode(':', $specArr[$ii]);
                        $target_spec_value_array[] = implode('|', $row_array);
                    } else {
                        $numArray = explode(':', $row_array[1]);
                        if ($buy_nums[$i] >= $numArray[2] && ($buy_nums[$i] <= $numArray[3] || $numArray[3] == -1)) {
                            $totalNum += $buy_nums[$i];
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                                $spec_price_arr[] = $numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                                $spec_price_arr[] = $numArray[1];
                            }
                            //[new] => 规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            //规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num(购入的数量):cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            $numArray[3] = $buy_nums[$i];
                            $row_array[1] = implode(':', $numArray);
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                    }
                }
                
                if ($totalNum <= 0) {
                    $error_tips .= L_('您至少要批发一种规格且满足对应的批发条件').'<br/>';
                }
                if ($marketGoods['express_template_id']>0 && $_POST['province_id']>0 && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $old_total_express_fee = $marketCartOld['total_express_fee'] > 0 ? $marketCartOld['total_express_fee'] : 0;
                        $marketCart['total_express_fee'] = $msg['total_express_fee'] + $old_total_express_fee;
                    }
                }
                
            } else {
                if ($totalNum) {
                    $totalNum += isset($_POST['buy_num']) ? intval($_POST['buy_num']) : 0;
                } else {
                    $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $marketGoods['min_num'];
                }
                
                if ($totalNum > $marketGoods['stock_num']) {
                    $error_tips .= L_('该商品库存不足').'<br/>';
                }
                if ($totalNum < $marketGoods['min_num']) {
                    $error_tips .= L_('一次至少要批发X1',$marketGoods['min_num'] . $marketGoods['unit']) .'<br/>';
                }

                if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                    $totalPrice = $totalNum * $marketGoods['vip_wholesale_price'];
                    $marketGoods['o_price'] = $marketGoods['price'];
                    $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
                } else {
                    $totalPrice = $totalNum * $marketGoods['price'];
                }
                if ($marketGoods['express_template_id']>0 && $_POST['province_id']>0 && $is_pick_in_store>0) {
                    $province_id = $_POST['province_id'];
                    $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $marketGoods, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $old_total_express_fee = $marketCartOld['total_express_fee'] > 0 ? $marketCartOld['total_express_fee'] : 0;
                        $marketCart['total_express_fee'] = $msg['total_express_fee'] + $old_total_express_fee;
                    }
                }
            }
            
            if (empty($error_tips)) {
                $discount_info = null;
                foreach ($marketGoods['discount_info'] as $discount) {
                    if ($discount['num'] <= $totalNum) {
                        if ($discount_info) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } else {
                            $discount_info = $discount;
                        }
                    }
                }
                $money = $totalPrice;
                if ($discount_info) {
                    $money = $totalPrice * $discount_info['discount'] * 0.1;
                }
                

                $marketCart['money'] = $money;
                $marketCart['total_price'] = $totalPrice;
                $marketCart['num'] = $totalNum;
                $min_price = $marketGoods['price'];
                if ($spec_price_arr) {
                    $spec_price = implode(',', $spec_price_arr);
                    $marketCart['spec_price'] = $spec_price;
                    $min_price = min($spec_price_arr);
                    if (!$min_price) {
                        $min_price = $marketGoods['price'];
                    }
                }
                $marketCart['price'] = $min_price;
                $marketCart['sell_mer_id'] = $marketGoods['mer_id'];
                $marketCart['sell_store_id'] = $marketGoods['store_id'];
//                 $marketOrder['status'] = 10;
                
                $marketCart['goods_id'] = $marketGoods['goods_id'];
                $marketCart['name'] = $marketGoods['name'];
                $marketCart['number'] = $marketGoods['number'];
                $marketCart['unit'] = $marketGoods['unit'];
                $marketCart['image'] = $marketGoods['image'];
                $marketCart['des'] = $marketGoods['des'];
                $marketCart['is_properties'] = $marketGoods['is_properties'];
                $marketCart['discount_info'] = $discount_info ? json_encode($discount_info) : '';

                // 是不是享受的vip价格  0 不是  1 是
                $marketCart['is_vip'] = $is_vip?1:0;

//                 $marketOrder['username'] = $username;
//                 $marketOrder['userphone'] = $userphone;
//                 $marketOrder['address'] = $address;
//                 $marketOrder['desc'] = $desc;
//                 $marketOrder['create_time'] = time();
                
                $marketCart['spec_value'] = $target_spec_value_array ? implode('#', $target_spec_value_array) : '';
                
                if ($marketCartOld) {
                    if ($cartid = M('Market_cart')->where(array('goods_id' => $goodsId, 'mer_id' => $this->merchant_session['mer_id']))->save($marketCart)) {
//                         $cartid = $marketOrderOld['cartid'];
//                         //删除已有的属性与规格的数据
//                         $where['order_id'] = $order_id;
//                         M('Market_order_properties')->where($where)->delete();
//                         $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
//                         foreach ($spec_list as $spec) {
//                             M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
//                         }
//                         M('Market_order_spec')->where($where)->delete();
                        $this->success(L_('加入购物车成功！'), U('cart'));
                        exit;
                    } else {
                        $this->error(L_('加入购物车失败！请重试！'));
                    }
                } else {
                    $cartid = M('Market_cart')->add($marketCart);
                }
                
                if ($cartid) {
//                     if ($marketGoods['is_properties']) {
//                         $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
//                         foreach ($properties as $pro_data) {
//                             $pro_data['cartid'] = $cartid;
//                             M('Market_cart_properties')->add($pro_data);
//                         }
//                     }
                    
//                     if ($marketGoods['spec_value']) {
//                         $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
//                         foreach ($spec_list as $spec) {
//                             $spec['cartid'] = $cartid;
//                             if (M('Market_cart_spec')->add($spec)) {
//                                 $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
//                                 foreach ($spec_value_list as $spec_value) {
//                                     $spec_value['cartid'] = $cartid;
//                                     M('Market_cart_spec_value')->add($spec_value);
//                                 }
//                             }
//                         }
//                     }
                    $this->success(L_('加入购物车成功！'), U('cart'));
                } else {
                    $this->error(L_('加入购物车失败！请重试！'));
                }
            } else {
                $this->error($error_tips);
            }
        }
    }
    
    private function cartCount()
    {
        $count = D('Market_cart')->where(array('mer_id' => $this->merchant_session['mer_id']))->count();
        $this->assign('count', intval($count));
    }
    
    public function cart()
    {
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;

        // 取下默认的城市id
        $database_area = D('Area');
        $condition_area['area_type'] = 1;
        $condition_area['is_open'] = 1;
        $area_info = $database_area->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->find();
        $province_id = $area_info['area_id'];

        if ($totalData = D('Market_total')->where(array('id' => $fid, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
            if ($totalData['status'] == 1) {
                $this->error(L_('此单已支付'));
            }
            $province_id = $totalData['province_id'];
            $databaseMarketGoods = D('Market_order');
            $cartList = $databaseMarketGoods->where(array('mer_id' => $this->merchant_session['mer_id'], 'fid' => $fid))->select();
            
            $goodsDB = D('Market_goods');
            foreach ($cartList as $cart) {
                $gds = $goodsDB->field('status')->where(array('goods_id' => $cart['goods_id']))->find();
                if (empty($gds) || $gds['status'] != 1) {
                    $this->error(L_('商品【X1】已下架，此单已失效，请重新下单订购其他商品',$cart['name']));
                }
            }
            if ($totalData['discount_totalMoney']) {
                $totalMoney = $totalData['discount_totalMoney'];
                $discountMoney = $totalData['money'] - $totalData['discount_totalMoney'];
            } else {
                $totalMoney = $totalData['money'];
                $discountMoney = 0;
            }
            $assign_array = array(
                'totalMoney' => $totalMoney,
                'discountMoney' => $discountMoney,
                'fid' => $fid,
                'totalCount' => count($cartList)
            );
            $m_area = M('Area');
            $address = array();
            if ($totalData['province_id'] > 0) {
                $address['province'] = $m_area->where(array('area_id' => $totalData['province_id']))->getField('area_name');
            }
            if ($totalData['city_id'] > 0) {
                $address['city'] = $m_area->where(array('area_id' => $totalData['city_id']))->getField('area_name');
            }
            if ($totalData['area_id'] > 0) {
                $address['area'] = $m_area->where(array('area_id' => $totalData['area_id']))->getField('area_name');
            }
            $this->assign('address', $address);
            $this->assign($assign_array);
            $this->assign(array('username' => $cartList[0]['username'], 'userphone' => $cartList[0]['userphone'], 'address' => $cartList[0]['address'], 'desc' => $cartList[0]['desc']));
        } else {
            $databaseMarketGoods = D('Market_cart');
            $cartList = D()->query('select c.*, m.name as merchantName from ' . C('DB_PREFIX') . 'market_cart as c inner join ' . C('DB_PREFIX') . 'merchant as m on c.sell_mer_id=m.mer_id where c.mer_id=' . $this->merchant_session['mer_id']);
            $this->assign(array('username' => $this->merchant_session['name'], 'userphone' => $this->merchant_session['phone'], 'address' => '', 'desc' => ''));
        }
        $goods_image_class = new goods_image();
        $databaseMarketGoods = D('Market_cart');
        $goodsDB = D('Market_goods');
        $list = array();
        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant_session['wholesale_discount']>0 ? $this->merchant_session['wholesale_discount'] : 100;
        $total_express_fee = 0;
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
        $this->assign('merchant', $merchant);
        foreach ($cartList as $cart) {
            $gds = $goodsDB->field('is_pick, pick_info, mer_id, status, freight_type, goods_volume, goods_weight, store_id, express_fee, express_template_id')->where(array('goods_id' => $cart['goods_id']))->find();
            if (empty($gds) || $gds['status'] != 1) {
                continue;
            }
            $buy_nums = array();
            // 配送费相关依据字段
            $cart['freight_type'] = $gds['freight_type']>0?$gds['freight_type']:1;
            $cart['goods_volume'] = $gds['goods_volume']>0?$gds['goods_volume']:0;
            $cart['goods_weight'] = $gds['goods_weight']>0?$gds['goods_weight']:0;
            $cart['store_id'] = $gds['store_id']>0?$gds['store_id']:0;
            $cart['express_fee'] = $gds['express_fee']>0?$gds['express_fee']:0;
            $cart['express_template_id'] = $gds['express_template_id']>0?$gds['express_template_id']:0;
            if(!empty($cart['image'])){
                $image = '';
                $tmp_pic_arr = explode(';', $cart['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) {
                        $image = $goods_image_class->get_image_by_path($value, 's');
                    }
                }
                $cart['image'] = $image;
            }
            $return = $databaseMarketGoods->format_spec_value($cart['spec_value'], $cart['goods_id'], $cart['cartid']);
            $cart['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $cart['list'] = isset($return['list']) ? $return['list'] : '';
            $cart['discount_info'] = json_decode($cart['discount_info'], true);

            if ($cart['list']) {
                foreach ($cart['list'] as $val) {
                    $buy_nums[$val['index']] = $val['stock_num'];
                }

                if ($province_id>0 && $buy_nums && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                if ($province_id>0 && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $cart['num'], $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }

            if ($wholesale_discount>0 && $wholesale_discount<100) {
                $cart['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
                $cart['discount_totalMoney'] = round($cart['money']*$wholesale_discount*0.01, 2);
            } else {
                $cart['discount_totalMoney'] = $cart['money'];
            }
            $cart['is_pick'] = false;
            // 配送费总值

            // 如果允许自提获取自提点信息
            if ($cart['is_pick_in_store'] == 0 && $gds['is_pick'] == 1 && $gds['pick_info']) {
                $pick_list = D('Pick_address')->get_pick_addr_by_merid($gds['mer_id'], true);
                $pick_info = explode(',', $gds['pick_info']);
                $cart['pick_address'] = array();
                foreach ($pick_list as $val) {
                    if (in_array($val['pick_addr_id'], $pick_info)) {
                        unset($val['pwd']);
                        $cart['pick_address'][] = $val;
                    }
                }
                if (empty($cart['pick_address'])) {
                    $cart['is_pick'] = false;
                } else {
                    $cart['is_pick'] = true;
                    $cart['has_check'] = 'pick';
                }
            }
            // 获取一下对应运费模板消息
            if ($cart['express_template_id'] > 0) {
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $cart['express_template_id']))->select();
                foreach ($template_msgs as $val) {
                    if ($val['type']==1) {
                        $express = true;
                    } elseif ($val['type']==2) {
                        $logistics = true;
                    }
                }
                if ($express && $logistics) {
                    $cart['is_type'] = 3; // 快递和物流都存在
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'express';
                    }
                } elseif ($express) {
                    $cart['is_type'] = 1; // 快递
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'express';
                    }
                } elseif ($logistics) {
                    $cart['is_type'] = 2; // 物流
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'logistics';
                    }
                } else {
                    $cart['is_type'] = 0; // 既没有快递也没有物流
                }
            } else {
                $cart['is_type'] = 0; // 既没有快递也没有物流
            }
            if (isset($list[$cart['sell_mer_id']])) {
                $list[$cart['sell_mer_id']]['cartlist'][]= $cart;
            } else {
                $list[$cart['sell_mer_id']] = array('name' => $cart['merchantName'], 'cartlist' => array($cart));
            }
        }
        $is_vip = false;
        if ($this->merchant_session['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant_session['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }

        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
        $pay_method_all = D('Config')->get_pay_method(0,0);
        $pay_method = D('Market_order')->pay_method(true, false);
        if ($pay_method_all['weixin']) {
            $pay_method['weixin'] = $pay_method_all['weixin'];
        }
        $this->assign('pay_method', $pay_method);
        $this->assign('merchant', $merchant);

        $this->assign('is_vip', $is_vip);
        $this->assign('cartList', $list);
        $this->display();
    }

    /**改变地址的时候计算运费*/
    public function getCartPrice()
    {
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;

        // 取下默认的城市id
        $province_id = isset($_POST['province_id']) ? intval($_POST['province_id']) : 0;

        if ($totalData = D('Market_total')->where(array('id' => $fid, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
            if ($totalData['status'] == 1) {
                $this->error(L_('此单已支付'));
            }
            $province_id = $totalData['province_id'];
        } else {
            $cartList = D()->query('select c.*, m.name as merchantName from ' . C('DB_PREFIX') . 'market_cart as c inner join ' . C('DB_PREFIX') . 'merchant as m on c.sell_mer_id=m.mer_id where c.mer_id=' . $this->merchant_session['mer_id']);
            $this->assign(array('username' => $this->merchant_session['name'], 'userphone' => $this->merchant_session['phone'], 'address' => '', 'desc' => ''));
        }
        $databaseMarketGoods = D('Market_cart');
        $goodsDB = D('Market_goods');
        $list = array();
        foreach ($cartList as &$cart) {
            $gds = $goodsDB->field('status, freight_type, goods_volume, goods_weight, store_id, express_fee, express_template_id')->where(array('goods_id' => $cart['goods_id']))->find();
            if (empty($gds) || $gds['status'] != 1) {
                continue;
            }
            $buy_nums = array();
            // 配送费相关依据字段
            $cart['freight_type'] = $gds['freight_type']>0?$gds['freight_type']:1;
            $cart['goods_volume'] = $gds['goods_volume']>0?$gds['goods_volume']:0;
            $cart['goods_weight'] = $gds['goods_weight']>0?$gds['goods_weight']:0;
            $cart['store_id'] = $gds['store_id']>0?$gds['store_id']:0;
            $cart['express_fee'] = $gds['express_fee']>0?$gds['express_fee']:0;
            $cart['express_template_id'] = $gds['express_template_id']>0?$gds['express_template_id']:0;
            $return = $databaseMarketGoods->format_spec_value($cart['spec_value'], $cart['goods_id'], $cart['cartid']);
            $cart['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $cart['list'] = isset($return['list']) ? $return['list'] : '';

            if ($cart['list']) {
                foreach ($cart['list'] as $val) {
                    $buy_nums[$val['index']] = $val['stock_num'];
                }
                if ($province_id>0 && $buy_nums && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                if ($province_id>0 && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $cart['num'], $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }

        }
        exit(json_encode($cartList));
    }


    public function goPay()
    {
        $fid = isset($_POST['fid']) ? intval($_POST['fid']) : 0;
        $totalMoney = 0;
        $total_express_fee = 0;
        $resultList = array();
        $databaseMarketTotal = D('Market_total');
        $databaseMarketGoods = D('Market_goods');
        $databaseMarketOrder = D('Market_order');
        $databaseMarketCart = D('Market_cart');
        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant_session['wholesale_discount']>0 ? $this->merchant_session['wholesale_discount'] : 100;
        // 购买总数
        $total_num = 0;
        $province_id = $_POST['province_id'] > 0 ? $_POST['province_id'] : 0;
        $city_id = $_POST['city_id'] > 0 ? $_POST['city_id'] : 0;
        $area_id = $_POST['area_id'] > 0 ? $_POST['area_id'] : 0;
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();

        if ($totalData = $databaseMarketTotal->where(array('id' => $fid, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
            if ($totalData['status'] == 1) {
                $this->error(L_('此单已支付'));
            }
            $resultList = $databaseMarketOrder->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'fid' => $fid))->select();
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                
                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                if(empty($marketGoods) || $marketGoods['status'] != 1){
                    //批发市场是没有这条数据的时候删除没有支付的订单所有的数据
                    $where['order_id'] = $order_id;
                    M('Market_order_properties')->where($where)->delete();
                    $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                    foreach ($spec_list as $spec) {
                        M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                    }
                    M('Market_order_spec')->where($where)->delete();
                    
                    $databaseMarketOrder->where(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']))->delete();
                    
                    //修改订单的总价
                    $databaseMarketTotal->where(array('id' => $fid, 'mer_id' => $this->merchant_session['mer_id']))->save(array('money' => $totalData['money'] - $marketOrder['money']));
                    $this->error(L_('商品【X1】在批发市场上已下架，不能购买了',$marketGoods['name']), U('market'));
                    exit();
                }
            }
            // 这里优先取平台折扣后的金额（兼容残留的数据）
            if ($totalData['discount_totalMoney'] > 0) {
                $totalMoney = $totalData['discount_totalMoney'];
            } else {
                $totalMoney = $totalData['money'];
            }
            if ($totalData['total_express_fee']>0) {
                $total_express_fee = $totalData['total_express_fee'];
            }
        } else {
            $cartIds = isset($_POST['cartids']) ? $_POST['cartids'] : null;
            if (empty($cartIds)) {
                $this->error(L_('请选择商品'));
            }
            $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
            $userphone = isset($_POST['userphone']) ? htmlspecialchars(trim($_POST['userphone'])) : '';
            $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
            $desc = isset($_POST['desc']) ? htmlspecialchars(trim($_POST['desc'])) : '';
            if (empty($username)) {
                $this->error(L_('收货人不能为空'));
                exit;
            }
            if (empty($userphone)) {
                $this->error(L_('联系电话不能为空'));
                exit;
            }
            if (empty($address)) {
                $this->error(L_('收货地址不能为空'));
                exit;
            }
            $list = $databaseMarketCart->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'cartid' => array('in', $cartIds)))->select();
            if ($list) {
                $orderList = array();
                foreach ($list as $marketOrder) {
                    $buy_nums = array();
                    $cartid = $marketOrder['cartid'];
                    $return = $databaseMarketCart->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $cartid);
                    $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                    
                    $where = array('goods_id' => $marketOrder['goods_id']);
                    $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                    if(empty($marketGoods) || $marketGoods['status'] != 1){
                        //$databaseMarketCart->where(array('mer_id' => $this->merchant_session['mer_id'], 'cartid' => $cartid))->delete();
                        $this->error(L_('商品【X1】在批发市场上已下架，不能购买了',$marketGoods['name']), U('cart'));
                        exit();
                    }
                    // 配送费相关依据字段
                    $marketOrder['freight_type'] = $marketGoods['freight_type']>0?$marketGoods['freight_type']:1;
                    $marketOrder['goods_volume'] = $marketGoods['goods_volume']>0?$marketGoods['goods_volume']:0;
                    $marketOrder['goods_weight'] = $marketGoods['goods_weight']>0?$marketGoods['goods_weight']:0;
                    $marketOrder['store_id'] = $marketGoods['store_id']>0?$marketGoods['store_id']:0;
                    $marketOrder['express_fee'] = $marketGoods['express_fee']>0?$marketGoods['express_fee']:0;
                    $marketOrder['express_template_id'] = $marketGoods['express_template_id']>0?$marketGoods['express_template_id']:0;
                    $marketOrder['sell_store_id'] = $marketGoods['store_id']>0?$marketGoods['store_id']:0;
                    $keys = 'pick_id_'.$cartid;
                    if ($_POST[$keys]) {
                        $marketOrder['pick_id'] = $_POST[$keys];
                        $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
                        $pick_msg = array();
                        foreach ($pick_list as $val) {
                            if ($marketOrder['pick_id'] == $val['pick_addr_id']) {
                                $pick_msg = $val;
                            }
                        }
                        if ($pick_msg) {
                            $marketOrder['username'] = $merchant['name'];
                            $marketOrder['userphone'] = $pick_msg['phone'];
                            $marketOrder['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                            if (!empty($pick_msg['province'])) {
                                $marketOrder['province_id'] = $pick_msg['province'];
                            }
                            if (!empty($pick_msg['city'])) {
                                $marketOrder['city_id'] = $pick_msg['city'];
                            }
                            if (!empty($pick_msg['area'])) {
                                $marketOrder['area_id'] = $pick_msg['area'];
                            }
                        }
                    } elseif (!$marketOrder['pick_id']) {
                        $marketOrder['province_id'] = $province_id;
                        $marketOrder['city_id'] = $city_id;
                        $marketOrder['area_id'] = $area_id;
                    }
                    if ($marketOrder['list']) {
                        $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                        $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                        
                        foreach ($marketOrder['list'] as $index => $row) {
                            if (isset($marketGoods['list'][$index])) {
                                if ($row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                                    $this->error(L_('购买商品【X1】的库存不足',$marketGoods['name']), U('cart'));
                                    exit();
                                } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                                    $this->error(L_('购买的商品【X1】的数量不足最小批发量',$marketGoods['name']), U('cart'));
                                    exit();
                                }
                            } else {
                                $this->error(L_('购买商品【X1】的已经没有这种规格了',$marketGoods['name']), U('cart'));
                                exit();
                            }
                            $buy_nums[$row['index']] = $row['stock_num'];
                        }
                        if ($province_id>0 && $buy_nums && $marketOrder['express_template_id']>0 && $marketOrder['is_pick_in_store']>0) {
                            $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketOrder, $marketOrder['is_pick_in_store']);
                            if ($msg['total_express_fee']>0) {
                                $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                            }
                        }
                    } elseif ($marketOrder['num'] > $marketGoods['stock_num']) {
                        $this->error(L_('购买商品【X1】的库存不足',$marketGoods['name']), U('cart'));
                        exit();
                    } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
                        $this->error(L_('购买商品【X1】的数量不足最小批发量',$marketGoods['name']), U('cart'));
                        exit();
                    } elseif (!$marketOrder['list']) {
                        if ($province_id>0 && $marketOrder['express_template_id']>0 && $marketOrder['is_pick_in_store']>0) {
                            $msg = D('Market_order')->market_express_template_fee($province_id, $marketOrder['num'], $marketOrder, $marketOrder['is_pick_in_store']);
                            if ($msg['total_express_fee']>0) {
                                $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                            }
                        }
                    }
                    
                    $totalMoney += $marketOrder['money'];
                    if ($marketOrder['total_express_fee']>0) {
                        $total_express_fee += $marketOrder['total_express_fee'];
                    }
                    // 处理下每个订单价格问题
                    if ($wholesale_discount>0 && $wholesale_discount<100) {
                        $wholesale_discount_money = $wholesale_discount;
                        $marketOrder['actual_money'] = $marketOrder['money'];
                        $marketOrder['money'] = round($marketOrder['money']*$wholesale_discount*0.01, 2);
                    } else {
                        $marketOrder['actual_money'] = $marketOrder['money'];
                        $wholesale_discount_money = 100;
                    }
                    $marketOrder['system_discount_info'] = $wholesale_discount_money;

                    $marketOrder['last_time'] = time();
                    $marketOrder['status'] = 10;
                    $marketOrder['username'] = $username;
                    $marketOrder['userphone'] = $userphone;
                    $marketOrder['address'] = $address;
                    $marketOrder['desc'] = $desc;
                    $marketOrder['create_time'] = time();
                    $orderList[] = $marketOrder;
                    
                }
                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $system_discount_info = $wholesale_discount/10; // 计算折扣
                    $discount_totalMoney = round($totalMoney*$wholesale_discount*0.01, 2);
                } else {
                    $system_discount_info = 10;
                    $discount_totalMoney = $totalMoney;
                }
                
                if ($orderList) {
                    $pay_type = $_POST['pay_type'] ? $_POST['pay_type'] : '';
                    //先生成父订单
                    $add_info = array(
                        'mer_id' => $this->merchant_session['mer_id'],
                        'province_id' => $province_id,
                        'city_id' => $city_id,
                        'area_id' => $area_id,
                        'status' => 0,
                        'money' => $totalMoney,
                        'total_express_fee' => $total_express_fee,
                        'system_discount_info' => $system_discount_info,
                        'discount_totalMoney' => $discount_totalMoney
                    );
                    $fid = $databaseMarketTotal->add($add_info);
                    if (empty($fid)) {
                        $this->error(L_('结算失败，稍后重试'));
                    }
                    // 把实际要付的钱赋值给原来处理金额字段
                    $totalMoney = $discount_totalMoney;
                    $order_info = array();
                    foreach ($orderList as $marketOrder) {
                        $cartid = $marketOrder['cartid'];
                        unset($marketOrder['cartid']);
                        $marketOrder['fid'] = $fid;
                        $marketOrder['pay_type'] = $pay_type;
                        if ($order_id = $databaseMarketOrder->add($marketOrder)) {
                            $marketOrder['order_id'] = $order_id;
                            $resultList[] = $marketOrder;
                            if ($marketOrder['is_properties']) {
                                $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $marketOrder['goods_id']))->select();
                                foreach ($properties as $pro_data) {
                                    $pro_data['order_id'] = $order_id;
                                    M('Market_order_properties')->add($pro_data);
                                }
                            }
                            
                            if ($marketOrder['spec_value']) {
                                $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $marketOrder['goods_id']))->select();
                                foreach ($spec_list as $spec) {
                                    $spec['order_id'] = $order_id;
                                    if (M('Market_order_spec')->add($spec)) {
                                        $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                        foreach ($spec_value_list as $spec_value) {
                                            $spec_value['order_id'] = $order_id;
                                            M('Market_order_spec_value')->add($spec_value);
                                        }
                                    }
                                }
                            }
                            $order_info[] = $order_id;
                            $databaseMarketCart->where(array('cartid' => $cartid))->delete();
                        } else {
                            $this->error(L_('下单失败！请重试！'));
                        }
                    }
                }
            } else {
                $this->error(L_('结算失败，稍后重试'));
            }
        }
        $merchant = D('Merchant')->field('money, name, phone, mer_spread_code,mer_id,reg_time')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
		
		if($this->config['nmgzhcs_appid']){
			$merchant['money'] = 0;			// 不使用余额
		}
        $totalMoney = $totalMoney + $total_express_fee;

        $nowtime = date("ymdHis");
        $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant_session['mer_id']);
        if ($pay_type=='' && $totalMoney > $merchant['money']) {
            $money = floatval($totalMoney - $merchant['money']);
            if (empty($money)||$money <0 ||!is_numeric($money)) {
                $this->error(L_('请输入正确的充值金额'));
            }
            
            $data_mer_recharge_order['mer_id'] = $this->merchant_session['mer_id'];
            $data_mer_recharge_order['money'] = $money;
            $data_mer_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_mer_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
            $data_mer_recharge_order['label'] = 'web_marketorder_' . $fid;
            if ($order_id = M('Merchant_recharge_order')->data($data_mer_recharge_order)->add()) {
                redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'merrecharge')));
            } else {
                $this->error_tips(L_('订单创建失败，请重试。'));
            }
        } elseif ($pay_type=='') {
            $returnData = D('Merchant_money_list')->use_money($this->merchant_session['mer_id'], $totalMoney, 'marketmulti', L_('购买批发市场的商品扣除商家余额'), $fid);
            if ($returnData['error_code']) {
                $this->error(L_('支付失败！请重试！'), U('buy_order'));
                exit();
            }
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                
                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();

                
                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3]!=-1) {
                                    $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;
                        if ($row['stock_num'] > 0) {
                            $total_num += $row['stock_num'];
                        }
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                } else {
                    $total_num += $marketOrder['num'];
                }
                
                $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];
                
                //修改订单状态
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('pay_time' => time(), 'status' => 1));
                
                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);
                
                $sms_data = array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => 0, 'type' => 'market');
                
                $sellmerchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
                
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $merchant['phone'];;
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
            }
            // 支付成功 查看是否存在推广者， 添加推广者分佣 添加开关控制
            if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $this->merchant_session['mer_id'])))->find();
                if ($spread_merchant) {
                    $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                    // 分佣不计算 配送费
                    $totalMoney = $totalMoney - $total_express_fee;

                    // 计算分佣金额
                    $spread_money = $totalMoney * $merchant_recommend_spread_ratio;
                    $spread_money = round($spread_money, 2); // 分佣金额

                    $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$totalMoney.$this->config['Currency_txt']));
                    $order_info['money'] = $spread_money;
                    $order_info['order_type'] = 'market_spread';
                    $order_info['mer_id'] = $spread_merchant['mer_id'];
                    $order_info['order_id'] = $fid;
                    $order_info['desc'] = $desc;
                    if ($total_num > 0) {
                        $order_info['num'] = $total_num;
                    }
                    D('Merchant_money_list')->add_money($order_info);
                    // 同步记录到记录表格中
                    $order_info['spread_mer_id'] = $merchant['mer_id'];
                    $order_info['spread_mer_name'] = $merchant['name'];
                    $order_info['spread_time'] = $merchant['reg_time'];
                    $order_info['add_time'] = time();
                    $info = M('Merchant_recommend_merchant_record')->data($order_info)->add();
                }
            }
            $databaseMarketTotal->where(array('id' => $fid))->save(array('orderid' => $orderid,'pay_time' => time(), 'status' => 1, 'pay_type' => ''));
            $this->success(L_('支付成功'), U('buy_order'));
        }  elseif ($pay_type=='weixin') {
            $data_tmp['pay_type'] = $pay_type;
            $data_tmp['order_type'] = 'markets';
            $data_tmp['order_id'] = $fid;
            $data_tmp['orderid'] = $orderid;
            $data_tmp['addtime'] = $nowtime;
            if(!D('Tmp_orderid')->add($data_tmp)){
                $this->error_tips(L_('更新失败，请联系管理员'));
            }
            $save_pay_id = $databaseMarketTotal->where(array("id"=>$fid))->save(array('orderid' => $orderid, 'pay_type' => $pay_type));
            if(!$save_pay_id){
                $this->error_tips(L_('更新失败，请联系管理员'));
            }
            $pay_class_name = ucfirst($_POST['pay_type']);
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                $this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }
            $now_order = D('Market_total')->field(true)->where(array("order_id"=>$fid))->find();
            if(empty($now_order)){
                $this->error_tips(L_('当前订单不存在'));
            }
            $pay_method_all = D('Config')->get_pay_method(0,0);
            $pay_method = D('Market_order')->pay_method(true, false);
            if ($pay_method_all['weixin']) {
                $pay_method['weixin'] = $pay_method_all['weixin'];
            }
            $now_order['order_type'] = 'markets';
            $now_order['order_id'] = $orderid;
            $pay_class = new $pay_class_name($now_order,$totalMoney,$pay_type,$pay_method[$pay_type]['config'],$this->merchant_session,0);
            $go_pay_param = $pay_class->pay();

            if(empty($go_pay_param['error'])){
                if($pay_class_name == 'Weixin'){
                    header("Content-type: application/json");
                    $returnArr = array(
                        'status'=>1,
                        'info'=>$go_pay_param['qrcode'],
                        'orderid'=>$orderid,
                    );
                    echo json_encode($returnArr);
                    exit;
                }else if(!empty($go_pay_param['url'])){
                    $this->assign('url',$go_pay_param['url']);
                }else if(!empty($go_pay_param['form'])){
                    $this->assign('form',$go_pay_param['form']);
                }else{
                    if(IS_AJAX){
                        $this->error(L_('调用支付发生错误，请重试。'));
                    }
                    else{
                        $this->error_tips(L_('调用支付发生错误，请重试。'));
                    }
                }
            }else{
                if(IS_AJAX){
                    $this->error($go_pay_param['msg']);
                }
                else{
                    $this->error_tips($go_pay_param['msg']);
                }
            }
        } elseif($pay_type=='offline') {
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $set_data = array(
                    'offline_money' => $marketOrder['money'] + $marketOrder['total_express_fee'],
                    'status' => 1,
                    'offline_status' => 0,
                    'is_mobile_pay' => 0,
                    'last_time' => time(),
                    'orderid' => $orderid
                );
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id))->data($set_data)->save();
            }
            $this->success(L_('支付成功'), U('buy_order'));
        } else {
            $this->error(L_('您选择的支付方式不存在，请更新支付方式！'));
        }

    }
    
    public function delCart()
    {
        $cartid = isset($_POST['cartid']) ? intval($_POST['cartid']) : 0;
        if ($cartid == -1) {
            D('Market_cart')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->delete();
            exit(json_encode(array('errCode' => 0, 'msg' => 'ok')));
        } else {
            if ($cart = D('Market_cart')->field(true)->where(array('cartid' => $cartid, 'mer_id' => $this->merchant_session['mer_id']))->find()) {
                D('Market_cart')->field(true)->where(array('cartid' => $cartid, 'mer_id' => $this->merchant_session['mer_id']))->delete();
                exit(json_encode(array('errCode' => 0, 'msg' => 'ok')));
            } else {
                exit(json_encode(array('errCode' => 1, 'msg' => L_('参数错误'))));
            }
        }
    }



    public function export()
    {
        $param = $_POST;
        if (isset($_POST['province_id']) && $_POST['province_id']) {
            $param['province_idss'] = intval($_POST['province_id']);
        }
        if (isset($_POST['city_id']) && $_POST['city_id']) {
            $param['city_idss'] = intval($_POST['city_id']);
        }
        $param['type'] = 'market_sell';
        $param['rand_number'] = time();
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }
}
