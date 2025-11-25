<?php
class MarketAction extends BaseAction
{
    public function index()
    {
        $data = array();
        $data['status'] = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $data['province_id'] = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $data['city_id'] = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $data['area_id'] = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $data[$area_index] = $this->system_session['area_id'];
        }
        
        if(!empty($_GET['keyword'])){
            $data['key'] = $_GET['keyword'];
        }
        
        $result = D('Market_goods')->getList($data, 'other');
        $this->assign($result);
        $this->display();
    }
    
    public function order()
    {
        $data = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $data[$area_index] = $this->system_session['area_id'];
        }
        if (isset($_GET['province_idss']) && $_GET['province_idss']) {
            $data['province_id'] = intval($_GET['province_idss']);
        }
        if (isset($_GET['city_idss']) && $_GET['city_idss']) {
            $data['city_id'] = intval($_GET['city_idss']);
        }
        if (isset($_GET['area_id']) && $_GET['area_id']) {
            $data['area_id'] = intval($_GET['area_id']);
        }

        if (isset($_GET['goods_id']) && $_GET['goods_id']) {
            $data['goods_id'] = intval($_GET['goods_id']);
        }
        if (isset($_GET['fid']) && $_GET['fid']) {
            $data['fid'] = intval($_GET['fid']);
            $this->assign('fid',$_GET['fid']);
        }
        if (isset($_GET['cid']) && $_GET['cid']) {
            $data['cid'] = intval($_GET['cid']);
            $this->assign('cid',$_GET['cid']);
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
        $category_list = D('Goods_wholesale_category')->where(array('fid'=>0))->select();
        $this->assign('fid_list',$category_list);
        $result = D('Market_order')->getList($data, 'other');
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

    public function ajax_cid(){
        $fid = $_GET['fid'] ? $_GET['fid'] : -1;
        $cids = $category_list = D('Goods_wholesale_category')->where(array('fid'=>$fid))->select();
        if(!$cids){
            $cids = [];
            echo json_encode($cids);
        }else{
            echo json_encode($cids);
        }
    }
    
    public function order_detail()
    {
        $order_id = intval($_GET['order_id']);
        $type = $_GET['type'];
        $where = array('order_id' => $order_id);
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
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



            // 处理支付流水  Payment Flow
            if ($marketOrder['fid'] > 0) {
                $total_order = D('Market_total')->where(array('id' => $marketOrder['fid']))->find();
                if ($total_order && $total_order['orderid']) {
                    $marketOrder['payment_flow'] = 'markets_' . $total_order['orderid'];
                }
                if ($total_order && $total_order['third_id']) {
                    $marketOrder['payment_third_id'] = $total_order['third_id'];
                }
                if ($total_order && $total_order['pay_time']) {
                    $marketOrder['pay_time'] = $total_order['pay_time'];
                }
                if ($total_order['status']==1) {
                    $marketOrder['paid'] = 1;
                } else {
                    $marketOrder['paid'] = 0;
                }
            } else {
                if ($marketOrder && $marketOrder['orderid']) {
                    $marketOrder['payment_flow'] = 'markets_' . $marketOrder['orderid'];
                }
                if ($marketOrder && $marketOrder['third_id']) {
                    $marketOrder['payment_third_id'] = $marketOrder['third_id'];
                }
                if ($marketOrder['status']>=1 && $marketOrder['status']<10) {
                    $marketOrder['paid'] = 1;
                } else {
                    $marketOrder['paid'] = 0;
                }
            }
            $pay_type_str = D('Pay')->get_pay_name($marketOrder['pay_type'], $marketOrder['is_mobile_pay'], $marketOrder['paid']);
            $marketOrder['pay_type_str'] = $pay_type_str;


            $this->assign('order', $marketOrder);
            
            $this->assign('merchant', M('Merchant')->field(true)->where(array('mer_id' => $marketOrder['sell_mer_id']))->find());
            $this->assign('merchant_store', M('Merchant_store')->field(true)->where(array('store_id' => $marketOrder['sell_store_id']))->find());
            $this->assign('buy_merchant', M('Merchant')->field(true)->where(array('mer_id' => $marketOrder['mer_id']))->find());
            
            $this->display();
        } else {
            $this->error('错误的订单信息');
        }
    }

    public function goods_detail()
    {
        $goodsId = intval($_GET['goods_id']);
        $order_id = intval($_GET['order_id']);
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->error('商品不存在！');
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
        
        $this->assign('merchant', M('Merchant')->field(true)->where(array('mer_id' => $marketGoods['mer_id']))->find());
        $this->assign('merchant_store', M('Merchant_store')->field(true)->where(array('store_id' => $marketGoods['store_id']))->find());
        
        $this->assign('marketGoods', $marketGoods);
        $this->display();
    }
    
    public function pull()
    {
        $order_id = intval($_GET['order_id']);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($marketOrder['status'] != 2) {
                exit(json_encode(array('error_code' => true, 'msg' => '此单暂时不能收货')));
            } else {
                if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))) {
                    //对方增加余额
                    $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                    $money = floatval($marketOrder['money']);
                    $total_money = $money + $total_express_fee;
                    $data = array();
                    $data['order_type'] = 'market';
                    $data['desc'] = '批发市场的商品【'.$marketOrder['name'].'】卖出时增加商家余额';
                    $data['mer_id'] = $marketOrder['sell_mer_id'];
                    $data['money'] = $total_money;
                    $data['order_id'] = $marketOrder['order_id'];
                    $data['total_money'] = $total_money;
                    // D('Merchant_money_list')->add_money($data);

                    D('SystemBill')->bill_method(0,$data);
                    
                    $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                    $merchant = M('Merchant')->where(array('mer_id' => $marketOrder['mer_id']))->find();
					$sms_data = array('mer_id' => $merchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $sellmerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = '您发给' . $merchant['name'] . '的' . $marketOrder['name'] . '共' . $marketOrder['num'] . $marketOrder['unit'] . '对方已经收货，订单号：' . $marketOrder['order_id'];
                    Sms::sendSms($sms_data);
                    exit(json_encode(array('error_code' => false, 'msg' => 'ok')));
                } else {
                    exit(json_encode(array('error_code' => true, 'msg' => '收货失败，稍后重试')));
                }
            }
        } else {
            exit(json_encode(array('error_code' => true, 'msg' => '订单信息错误')));
        }
    }
    
    public function goods_del()
    {
        $goods_id = intval($_POST['id']);
        $where = array('goods_id' => $goods_id);
        if ($delete = D('Market_goods')->where($where)->delete()) {
            D('Market_goods_properties')->where($where)->delete();
            $specs = D('Market_goods_spec')->field('id')->where($where)->select();
            $sids = array();
            foreach ($specs as $spec) {
                $sids[] = $spec['id'];
            }
            D('Market_goods_spec')->where($where)->delete();
            D('Market_goods_spec_value')->where(array('sid' => array('in', $sids)))->delete();
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }
    
    public function change()
    {
        $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
        $goods = D('Market_goods')->field(true)->where(array('goods_id' => $goods_id))->find();
        if (empty($goods)) {
            $this->error('商品信息不存在');
        }
        if (IS_POST) {
            $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
            $sort = isset($_POST['sort']) ? intval($_POST['sort']) : 0;
            if (D('Market_goods')->where(array('goods_id' => $goods_id))->save(array('last_time' => time(), 'status' => $status, 'sort' => $sort))) {
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！');
            }
        } else {
            $this->assign('goods', $goods);
            $this->display();
        }
    }


    public function export()
    {
        $param = $_POST;
        $param['type'] = 'market_sell';
        $param['rand_number'] = time();
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }
}