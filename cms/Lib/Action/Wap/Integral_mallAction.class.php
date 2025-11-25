<?php
class Integral_mallAction extends BaseAction{

    //首页
    public function index()
    {
        $mer_id = I('mer_id',0,'intval');
        if(empty($mer_id)){
            $this->error('参数错误！');
        }

        $slider_model = D('Merchant_integral_mall_slider');
        $advert_model = D('Merchant_integral_mall_advert');

        //顶部轮播图
        $top_advert = $advert_model->get_list($mer_id,'index_top_advert',5);
        $this->assign('top_advert',$top_advert);

        //中间一张广告图
        $middle_advert = $advert_model->get_list($mer_id,'index_middle_single_advert',1);
        $this->assign('middle_advert',$middle_advert);

        //导航
        $slider = $slider_model->get_slider($mer_id,'index_slider');

        $this->assign('slider',$slider);

        // 首页
        $this->display();
    }

    public function ajax_goods_page_list(){
        $mer_id = I('mer_id',0,'intval');
        $cat_id = intval($_POST['cat_id']);
        $model = D('Merchant_integral_mall_goods');
        $where = array();
        $where['is_del'] = 0;
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        if(isset($_POST['score'])){
            $score = intval($_POST['score']);
            $where['payment_pure_integral'] = array('elt',$score);
        }
        if(!empty($_POST['keyword'])){
            $where['goods_name'] = array('like','%'.trim($_POST['keyword']).'%');
        }
        if($cat_id){
            $cat_id_arr = M('Merchant_integral_mall_category')->where(array('cat_fid' => $cat_id))->field('cat_id')->select();
            $cat_arr = array();
            foreach ($cat_id_arr as $val) {
                $cat_arr[] = $val['cat_id'];
            }
            $where['cat_id'] = array('in', $cat_arr);
        }
        $data = $model->Merchant_integral_mall_goods_page_list($where,$field = true, $order = 'goods_id desc', $pageSize = 20);
        echo json_encode($data);
    }

    public function goodsDetail()
    {
        // 商品详情页面
        $model = D('Merchant_integral_mall_goods');
        $d_market_goods = D('Market_goods');
        $goods_id = I('goods_id',0,'intval');
        $mer_id = I('mer_id',0,'intval');
        $where = array();
        $where['goods_id'] = $goods_id;
        if ($mer_id>0) {
            $where['mer_id']=$mer_id;
        }
        $data = $model->goods_detail($where);
        $data = $data['detail'];
        if (!$data || 1==$data['is_del'] || 0==$data['status']) {
            $this->error_tips('当前商品不存在或者已经被删除！');
        }
        if ($data['goods_source_type']==3) {
            $virtual_goods_id = $data['virtual_wholesale_goods_id']>0 ? $data['virtual_wholesale_goods_id'] : $data['market_goods_id'];
            $where['goods_id'] = $virtual_goods_id;
            $where['status'] = 1;
            $market_goods = $d_market_goods->where($where)->find();
            $data['express_template_id'] = $market_goods['express_template_id'];
            $data['express_fee'] = $market_goods['express_fee'];
            if ($data['spec_val']) {
                $info_list = $data['spec_val']['list'];
                $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                $list = isset($return['list']) ? $return['list'] : '';
                foreach ($info_list as $key1=>&$val1) {
                    if ($list[$key1]) {
                        if ($list[$key1]['sku']>0) {
                            $val1['sku'] = min($list[$key1]['stock_num'], $val1['sku']);
                        } elseif ($list[$key1]['sku'] != -1) {
                            $val1['sku'] = 0;
                        }
                        $val1['exchange_limit_num'] = min($val1['exchange_limit_num'], $val1['sku']);
                    }
                }
                $data['spec_val']['list'] = $info_list;
            }

        }
        if (!empty($data['wap_pic_list'])) {
            $wap_image = reset($data['wap_pic_list']);
            $data['image'] = $wap_image['url'];
        }
        $uid=$this->user_session['uid'];
        if ($data['spec_val']) {
            $data['is_spec'] = true;
            // 查询用户兑换数量信息
            $where_buy['goods_id'] = $goods_id;
            $where_buy['uid'] = $uid;
            $where_buy['status'] = array('in', array(0,1,2,3,5));
            $where_buy['is_del'] = 0;
            if ($mer_id>0) {
                $where_buy['mer_id'] = $mer_id;
            }
            $msg = M('Merchant_integral_mall_goods_order')->where($where_buy)->group('spec_index')->getField('spec_index, count(1) as count_num');
            $this->assign('msg',$msg);
            $this->assign('buy_num',0);
            // 处理规格是否存在问题
            if ($data['spec_val'] && $data['spec_val']['list']) {
                // 把 对应规格数量等信息循环取键值组成数组 array_merge   array_unique
                $spec_key_arr = array();
                foreach ($data['spec_val']['list'] as $key_item => $list_item) {
                    $item_arr = explode('_',$key_item);
                    $spec_key_arr = array_merge($spec_key_arr,$item_arr);
                }
                $spec_key_arr = array_unique($spec_key_arr);
                $data['spec_val']['spec_key_arr'] = $spec_key_arr;
            }
        } else {
            $data['is_spec'] = false;
            // 查询用户兑换数量信息
            $where_buy['goods_id'] = $goods_id;
            $where_buy['uid'] = $uid;
            $where_buy['status'] = array('in', array(0,1,2,3,5));
            $where_buy['is_del'] = 0;
            if ($mer_id>0) {
                $where_buy['mer_id'] = $mer_id;
            }
            $buy_num = M('Merchant_integral_mall_goods_order')->where($where_buy)->count();
            if ($data['exchange_limit_num'] > 0 && $data['exchange_limit_num'] <= $buy_num) {
                $this->error_tips('当前商品您已经兑换过了，不能在进行兑换！');
            }
            $this->assign('buy_num',$buy_num);
        }
        $data['sku'] = intval($data['sku']) - intval($data['sale_count']);

        $this->assign('goods',$data);
        $this->display();
    }
    // 订单确认页面
    public function confirm_order()
    {
        $order_id = I('order_id',0,'intval');
        $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']) : cookie('userLocationId');
        $goods_id = I('goods_id',0,'intval');
        $num = I('num',0,'intval');
        $spec_index = I('spec_index','','trim');
        $user_address = D('User_adress')->get_one_adress($this->user_session['uid'], intval($address_id));

        $d_merchant_integral_mall_goods_order = D('Merchant_integral_mall_goods_order');
        $d_merchant_integral_mall_goods = D('Merchant_integral_mall_goods');
        if(empty($this->user_session['uid'])){
            $this->error_tips('您还没有登陆，请先登录',U('Login/index'));
            redirect(U('Login/index'));
        }
        $uid = $this->user_session['uid'];
        if($order_id > 0){
            $where = array();
            $where['order_id'] = $order_id;
            $where['uid']=$uid;
            $order = $d_merchant_integral_mall_goods_order->get_order_detail($where);
            if (empty($order)) {
                $this->error_tips('订单不存在！');
            }
            if ($order['paid']>=1) {
                $this->error_tips('订单已支付！');
            }
            $num = $order['num'];
            $order['desc'] = trim($order['desc']);
            $this->assign('order', $order);
            $this->assign('order_id', $order_id);
            $mer_id = $order['mer_id'];
            $goods_where = array();
            $goods_where['goods_id'] = $order['goods_id'];
            $goods_detail = $d_merchant_integral_mall_goods->goods_detail($goods_where);
            $detail = $goods_detail['detail'];
            $this->assign('detail', $detail);
        }elseif ($goods_id>0) {
            if ($num <= 0) {
                $this->error_tips('最少购买一份！');
            }
            $goods_where = array();
            $goods_where['goods_id'] = $goods_id;
            $goods_detail = $d_merchant_integral_mall_goods->goods_detail($goods_where);
            $detail = $goods_detail['detail'];
            $this->assign('detail', $detail);
            $mer_id = $detail['mer_id'];
        } else {
            $this->error_tips('信息错误！');
        }

        // 获取对应商家信息
        $merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
        $this->assign('merchant',$merchant);

        $this->assign('user_adress', $user_address);

        $pick_addr_id = isset($_GET['pick_addr_id']) ? $_GET['pick_addr_id'] : '';
        $pick_list = D('Pick_address')->get_pick_addr_by_merid($mer_id, true);
        $pick_address = array();
        if ($pick_addr_id) {
            foreach ($pick_list as $k => $v) {
                if ($v['pick_addr_id'] == $pick_addr_id) {
                    $pick_address = $v;
                    break;
                }
            }
        } else {
            $pick_address = $pick_list[0];
        }
        if (!empty($pick_address)) {
            $pick_address['distance'] = $this->wapFriendRange($pick_address['distance']);
        }

        $this->assign('goods_id',$goods_id);
        $deliver_type = $detail['deliver_type'];

        $virtual_goods_id = $detail['virtual_wholesale_goods_id']>0 ? $detail['virtual_wholesale_goods_id'] : $detail['market_goods_id'];
        if ($detail['goods_source_type']==3) {
            $d_market_goods=D('Market_goods');
            $where['goods_id']=$virtual_goods_id;
            $where['status']=1;
            $market_goods = $d_market_goods->where($where)->find();
            if ($market_goods) {
                // 虚拟批发直接同步
                $detail['express_template_id'] = $market_goods['express_template_id'];
                $detail['express_fee'] = $market_goods['express_fee'];
            }
        }

        // 如果存在规格，且规格选择了，去除对应规格信息
        // 实际批发的价格 * 数量
        $is_vip = false;
        if ($merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $virtual_goods_id);
        $market_goods = $databaseMarketGoods->field(true)->where($where)->find();
        $market_price = 0;
        $market_money = 0;
        if ($spec_index) {
            if ($order) {
                $spec_list = $order['spec_value']['list'];
            } else {
                $spec_list = $detail['spec_val']['list'];
            }
            $spec_info = $spec_list[$spec_index];
            if (empty($spec_info)) {
                $this->error_tips('抱歉！选择的规格有误！');
            }
            $this->assign('spec_info',$spec_info);
            $basic_price = $spec_info['payment_pure_integral'] * $num;

            if ($detail['spec_val']['list'][$spec_index]) {
                $goods_spec = $detail['spec_val']['list'][$spec_index];
                $sell_num = $goods_spec['sell_num'];
                $stock_num = $goods_spec['stock_num'];
                $exchange_limit_num = $goods_spec['exchange_limit_num'];
                if ($num > ($stock_num - $sell_num)) {
                    $this->error_tips('抱歉！商品库存不足！');
                }
                if ($exchange_limit_num>0 && $num > $exchange_limit_num) {
                    $this->error_tips('抱歉！限制兑换'. $exchange_limit_num . '份！');
                }
            }
            if ($detail['goods_source_type']==3) {
                $spec_ret = $databaseMarketGoods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                $spec_list = isset($spec_ret['spec_list']) ? $spec_ret['spec_list'] : '';
                $list = isset($spec_ret['list']) ? $spec_ret['list'] : '';
                if ($spec_list && $list && $list[$spec_index]) {
                    $spec_goods = $list[$spec_index];
                    if ($is_vip) {
                        $market_price = $spec_goods['vip_wholesale_price'] ? floatval($spec_goods['vip_wholesale_price']) : floatval($spec_goods['price']);
                        $market_money = $market_price * $num;
                    } else {
                        $market_price = floatval($spec_goods['price']);
                        $market_money = $market_price * $num;
                    }
                }
            }

        } else if ($order){
            $basic_price = $order['payment_pure_integral'] * $num;
            $sale_count = $detail['sale_count'];
            $sku = $detail['sku'];
            if ($num > ($sku - $sale_count)) {
                $this->error_tips('抱歉！商品库存不足！');
            }
            if ($detail['goods_source_type']==3) {
                if ($is_vip) {
                    $market_price = $market_goods['vip_wholesale_price'] ? floatval($market_goods['vip_wholesale_price']) : floatval($market_goods['price']);
                    $market_money = $market_price * $num;
                } else {
                    $market_price = floatval($market_goods['price']);
                    $market_money = $market_price * $num;
                }
            }
        } else {
            $basic_price = $detail['payment_pure_integral'] * $num;
            $sale_count = $detail['sale_count'];
            $sku = $detail['sku'];
            $exchange_limit_num = $detail['exchange_limit_num'];
            if ($num > ($sku - $sale_count)) {
                $this->error_tips('抱歉！商品库存不足！');
            }
            if ($exchange_limit_num>0 && $num > $exchange_limit_num) {
                $this->error_tips('抱歉！限制兑换'. $exchange_limit_num . '份！');
            }
            if ($detail['goods_source_type']==3) {
                if ($is_vip) {
                    $market_price = $market_goods['vip_wholesale_price'] ? floatval($market_goods['vip_wholesale_price']) : floatval($market_goods['price']);
                    $market_money = $market_price * $num;
                } else {
                    $market_price = floatval($market_goods['price']);
                    $market_money = $market_price * $num;
                }
            }
        }

        if ($detail['goods_source_type']==3) {
            $express_template_info = $d_merchant_integral_mall_goods_order->express_template_fee($user_address, $detail, $num, $spec_index, 1);
            if (is_array($express_template_info) && $express_template_info['error']) {
                $this->error_tips($express_template_info['err_msg']);
            }
            $total_ = $market_money + $express_template_info;
            if ($express_template_info > 0 && $detail['freight_charges']==1 && $merchant['money'] < $total_) {
                $this->error_tips('当前库存不足，请联系商家下单！');
            } elseif ($merchant['money'] < $market_money) {
                $this->error_tips('当前库存不足，请联系商家下单！');
            } elseif ($detail['freight_charges']==1) {
                // 商家承担运费，用户需要支付运费
                $express_template_info = 0;
            }

            $express_template_info = $express_template_info > 0 ? $express_template_info : 0;
            $this->assign('express_fee',$express_template_info);
            $deliver_type = 0;
        } elseif (empty($pick_address)) {
            $this->error_tips('抱歉！商家暂未添加自提点，暂时无法下单！');
        } else {
            $this->assign('pick_addr_id', $pick_addr_id);
            $this->assign('pick_address', $pick_address);
        }
        $this->assign('delivery_type',$deliver_type);

        // 查询对应商家会员卡信息
        $now_card = M('Card_new')->where(array('mer_id' => $mer_id))->find();
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid, $mer_id);
        $now_user_card = M('Card_userlist')->where(array('uid'=>$uid,'mer_id'=> $mer_id))->find();
        if(!empty($now_user_card) && $now_user_card['status']==0){
            $this->error_tips('您的会员卡不能使用！');
        }
        $this->assign('now_card', $now_card);
        if (empty($card_info['id'])) {
            if ($now_card['self_get'] || $now_card['auto_get']) {
                $result = D('Card_new')->auto_get($uid, $mer_id);
                if (!$result['error_code']) {
                    $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid, $mer_id);
                } else {
                    $this->error_tips($result['msg']);
                }
            } else {
                $this->error_tips('您没有会员卡，请联系商家开通！');
            }
        } elseif ($card_info['status'] == 0) {
            $this->error_tips('您的会员卡不能使用！');
        }
        if ($basic_price > $now_user_card['card_score']) {
            $this->error_tips('您的会员卡积分当前不足！剩余' . $now_user_card['card_score']);
        }
        $this->assign('card_info',$card_info);

        $this->assign('basic_price',$basic_price);

        if ($detail['freight_charges'] == 1 && $detail['goods_source_type'] == 3) {
            $pay_price = 0; // 运费商家承担
        } else {
            $pay_price = $express_template_info>0?$express_template_info:0;
        }
        $this->assign('pay_price',$pay_price);


        $this->display();
    }

    public function save_order()
    {

        $address_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']) : 0;
        $order_id=I('order_id','','intval');
        $goods_id = I('goods_id',0,'intval');
        $num = I('num',0,'intval');
        $spec_index = I('spec_index','','trim');
        $user_address = D('User_adress')->get_one_adress($this->user_session['uid'], intval($address_id));
        $d_merchant_integral_mall_goods_order = D('Merchant_integral_mall_goods_order');
        $d_merchant_integral_mall_goods = D('Merchant_integral_mall_goods');

        $uid=$this->user_session['uid'];

        if($order_id > 0){
            $where = array();
            $where['order_id'] = $order_id;
            $where['uid'] = $uid;
            $order = $d_merchant_integral_mall_goods_order->get_order_detail($where);
            if (empty($order)) {
                $this->error_tips('该订单信息错误！');
            }
            $num = $order['num'];
            $mer_id = $order['mer_id'];
            $goods_where = array();
            $goods_where['goods_id'] = $order['goods_id'];
            $goods_detail = $d_merchant_integral_mall_goods->goods_detail($goods_where);
            if (empty($goods_detail['detail'])) {
                $this->error_tips('物品信息错误！');
            }
            $detail = $goods_detail['detail'];
        }elseif ($goods_id>0) {
            if ($num <= 0) {
                $this->error_tips('最少购买一份！');
            }
            $goods_where = array();
            $goods_where['goods_id'] = $goods_id;
            $goods_detail = $d_merchant_integral_mall_goods->goods_detail($goods_where);
            if (empty($goods_detail['detail'])) {
                $this->error_tips('物品信息错误！');
            }
            $detail = $goods_detail['detail'];
            $mer_id = $detail['mer_id'];
        } else {
            $this->error_tips('参数信息错误！');
        }

        // 如果存在规格，且规格选择了，去除对应规格信息
        if ($spec_index) {
            if ($order) {
                $spec_list = $order['spec_val']['list'];
            } else {
                $spec_list = $detail['spec_val']['list'];
            }
            $spec_info = $spec_list[$spec_index];
            if (empty($spec_info)) {
                $this->error_tips('抱歉！选择的规格有误！');
            }
            $basic_price = $spec_info['payment_pure_integral'] * $num;

            if ($detail['spec_val']['list'][$spec_index]) {
                $goods_spec = $detail['spec_val']['list'][$spec_index];
                $sell_num = $goods_spec['sell_num'];
                $stock_num = $goods_spec['stock_num'];
                if ($num > ($stock_num - $sell_num)) {
                    $this->error_tips('抱歉！商品库存不足！');
                }
            }
            $payment_pure_integral = $spec_info['payment_pure_integral'];

        } else if ($order){
            $basic_price = $order['payment_pure_integral'] * $num;
            $sale_count = $detail['sale_count'];
            $sku = $detail['sku'];
            if ($num > ($sku - $sale_count)) {
                $this->error_tips('抱歉！商品库存不足！');
            }
            $payment_pure_integral = $order['payment_pure_integral'];
        } else {
            $basic_price = $detail['payment_pure_integral'] * $num;
            $sale_count = $detail['sale_count'];
            $sku = $detail['sku'];
            if ($num > ($sku - $sale_count)) {
                $this->error_tips('抱歉！商品库存不足！');
            }
            $payment_pure_integral = $detail['payment_pure_integral'];
        }
        // 虚拟批发直接同步
        $virtual_goods_id = $detail['virtual_wholesale_goods_id']>0 ? $detail['virtual_wholesale_goods_id'] : $detail['market_goods_id'];
        if ($detail['goods_source_type']==3) {
            $d_market_goods=D('Market_goods');
            $where['goods_id']=$virtual_goods_id;
            $where['status']=1;
            $market_goods = $d_market_goods->where($where)->find();
            if ($market_goods) {
                // 虚拟批发直接同步
                $detail['express_template_id'] = $market_goods['express_template_id'];
                $detail['express_fee'] = $market_goods['express_fee'];
            }
        }
        $express_fee = 0;
        $deliver_type = $detail['deliver_type'];
        $pick_list = D('Pick_address')->get_pick_addr_by_merid($mer_id, true);
        if ($detail['goods_source_type']==3) {
            $express_template_info = $d_merchant_integral_mall_goods_order->express_template_fee($user_address, $detail, $num, $spec_index, 1);
            if (is_array($express_template_info) && $express_template_info['error']) {
                $this->error_tips($express_template_info['err_msg']);
            }
            $express_fee = $express_template_info > 0 ? $express_template_info : 0; // 配送费
            $deliver_type = 0;
        } elseif (empty($pick_list)) {
            $this->error_tips('抱歉！商家暂未添加自提点，暂时无法下单！');
        } else {
            $deliver_type = 1;
        }

        if (IS_POST) {
            $phone = isset($_POST['ouserTel']) ? htmlspecialchars($_POST['ouserTel']) : '';
            $name = isset($_POST['ouserName']) ? htmlspecialchars($_POST['ouserName']) : '';
            $address = isset($_POST['ouserAddres']) ? htmlspecialchars($_POST['ouserAddres']) : '';
            $pick_address = isset($_POST['pick_address']) ? htmlspecialchars($_POST['pick_address']) : '';
            $pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : 0;

            $note = isset($_POST['omark']) ? htmlspecialchars($_POST['omark']) : '';


            if ($deliver_type == 0) {//配送方式是：快递
                if (empty($name)) $this->error_tips('联系人不能为空');
                if (empty($phone)) $this->error_tips('联系电话不能为空');
            }
            $now_time = time();
            $order_data = array();
            if ($order_id <= 0) {
                $order_data['mer_id'] = $mer_id;
                $order_data['goods_id'] = $goods_id;
                $order_data['goods_name'] = $detail['unit'];
                $order_data['goods_name'] = $detail['goods_name'];
                $order_data['pc_pic'] = $detail['pc_pic'];
                $order_data['wap_pic'] = $detail['wap_pic'];
                $order_data['payment_pure_integral'] = $payment_pure_integral;
                $order_data['num'] = $num;
                if ($spec_index) {
                    $order_data['spec_value'] = serialize($detail['spec_val']);
                    $order_data['spec_index'] = $spec_index;
                }
                $order_data['freight_charges'] = $detail['freight_charges']; // 运费承担方 0 用户 1 发布商户
                $order_data['total_integral'] = $basic_price;
                $order_data['num'] = $num;
                $orderid  = date('ymdhis').substr(microtime(),2,8-strlen($this->user_session['uid'])).$this->user_session['uid'];
                $order_data['orderid'] = $orderid;
                $order_data['create_time'] = $now_time;
                $order_data['uid'] = $uid;
            } else {
                $orderid = $order['orderid'];
            }
            $order_data['desc'] = $note;
            $order_data['last_time'] = $now_time;

            if ($deliver_type == 1) {//自提
                $order_data['is_pick_in_store'] = 0;//配送方式 0：到店自提，1：供货商家配送
                $delivery_fee = $order_data['freight_charge'] = 0;//运费
                $order_data['username'] = isset($this->user_session['nickname']) && $this->user_session['nickname'] ? $this->user_session['nickname'] : '';
                $order_data['userphone'] = isset($this->user_session['phone']) && $this->user_session['phone'] ? $this->user_session['phone'] : '';
                $order_data['address'] = $pick_address;
                $order_data['address_id'] = 0;
                $pick_id = substr($pick_id, 1);
                if (empty($pick_id)) {
                    $this->error_tips('抱歉！请选择自提地点！');
                }
                $order_data['pick_id'] = $pick_id;
                $order_data['province'] = isset($this->user_session['province_id']) && $this->user_session['province_id'] ? $this->user_session['province_id'] : 0;
                $order_data['city'] = isset($this->user_session['city_id']) && $this->user_session['city_id'] ? $this->user_session['city_id'] : 0;
                $order_data['area'] = isset($this->user_session['area_id']) && $this->user_session['area_id'] ? $this->user_session['area_id'] : 0;

            } else {//配送
                $order_data['username'] = $name;
                $order_data['userphone'] = $phone;
                $order_data['address'] = $address;
                $order_data['address_id'] = $address_id;
                $order_data['latitude'] = $user_address['latitude'];
                $order_data['longitude'] = $user_address['longitude'];
                $order_data['province'] = $user_address['longitude'];
                $order_data['city'] = $user_address['city'];
                $order_data['area'] = $user_address['area'];
                $delivery_fee = $order_data['freight_charge'] = $express_fee;//运费
                $order_data['is_pick_in_store'] = 1;//配送方式 0：到店自提，1：供货商家配送
            }

            $order_data['total_money'] = $delivery_fee;// 当前只有运费
            // 获取对应商家信息
            $merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();

            // 如果是虚拟批发同步到对应商家添加批发订单 $detail
            if ($detail && $detail['goods_source_type']==3) {
                $virtual_goods_id = $detail['virtual_wholesale_goods_id']>0 ? $detail['virtual_wholesale_goods_id'] : $detail['market_goods_id'];
                if ($order_id>0) {
                    $marketOrderOld = M('Market_order')->field(true)->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id, 'goods_id' => $virtual_goods_id, 'mer_id' => $mer_id, 'status' => 10))->find();
                }

                $is_vip = false;
                if ($merchant['type_id']>0) {
                    $merchant_type = M('Merchant_type')->where(array('type_id'=>$merchant['type_id']))->find();
                    $is_vip = (1==$merchant_type['is_vip'])?true:false;
                }
                $databaseMarketGoods = D('Market_goods');
                $where = array('goods_id' => $virtual_goods_id);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                if(empty($marketGoods)){
                    $this->error_tips(L_('商品不存在！'));
                }
                $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';

                $totalPrice = 0;
                if ($marketGoods['spec_list']) {
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    $target_spec_value_array = array();
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                        $key = str_replace(':', '_', $row_array[0]);
                        $numArray = explode(':', $row_array[1]);
                        if ($key == $spec_index && intval($num) >= intval($numArray[2]) && (intval($num) <= intval($numArray[3]) || intval($numArray[3])==-1)) {
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $num * $numArray[7];
                                $spec_price_arr[]=$numArray[7];
                            } else {
                                $totalPrice += $num * $numArray[1];
                                $spec_price_arr[]=$numArray[1];
                            }
                            //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            $numArray[3] = $num;
                            $numArray[2] = 0;
                            $row_array[1] = implode(':', $numArray);
                            $target_spec_value_array[] = implode('|', $row_array);
                        } elseif ($key == $spec_index && intval($num) > $numArray[3] && $numArray[3] != -1) {
                            $this->error_tips('库存不足！');
                        }
                    }
                    $total_ = $totalPrice + $express_fee;
                    if ($express_fee > 0 && $detail['freight_charges']==1 && $merchant['money'] < $total_) {
                        $this->error_tips('当前库存不足，请联系商家下单！');
                    } elseif ($merchant['money'] < $totalPrice) {
                        $this->error_tips('当前库存不足，请联系商家下单！');
                    }
                } else {
                    if ($num > $marketGoods['stock_num']) {
                        $this->error_tips('该商品库存不足！');
                    }
                    if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                        if ($marketGoods['price'] && $marketGoods['price']>0) {
                            $marketGoods['o_price'] = $marketGoods['price'];
                        }
                        $totalPrice = $num * $marketGoods['vip_wholesale_price'];
                        $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
                    } else {
                        $totalPrice = $num * $marketGoods['price'];
                    }
                    $total_ = $totalPrice + $express_fee;
                    if ($express_fee > 0 && $detail['freight_charges']==1 && $merchant['money'] < $total_) {
                        $this->error_tips('当前库存不足，请联系商家下单！');
                    } elseif ($merchant['money'] < $totalPrice) {
                        $this->error_tips('当前库存不足，请联系商家下单！');
                    }
                }
                $discount_info = '';
                foreach ($marketGoods['discount_info'] as $discount) {
                    if ($discount['num'] <= $num) {
                        if ($discount_info) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } else {
                            $discount_info = $discount;
                        }
                    }
                }

                $marketOrder = array('mer_id' => $mer_id, 'last_time' => time());
                $marketOrder['actual_money'] = $totalPrice;
                $marketOrder['system_discount_info'] = 100;
                $marketOrder['total_express_fee'] = $express_fee;

                // 地址相关信息
                $marketOrder['province'] = isset($this->user_session['province_id']) && $this->user_session['province_id'] ? $this->user_session['province_id'] : 0;
                $marketOrder['city'] = isset($this->user_session['city_id']) && $this->user_session['city_id'] ? $this->user_session['city_id'] : 0;
                $marketOrder['area'] = isset($this->user_session['area_id']) && $this->user_session['area_id'] ? $this->user_session['area_id'] : 0;
                // 配送类型
                $marketOrder['is_pick_in_store'] = $order_data['is_pick_in_store'];
                $marketOrder['pick_id'] = $pick_id;

                $marketOrder['money'] = $totalPrice;
                $marketOrder['total_price'] = $totalPrice;
                $marketOrder['num'] = $num;

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
                if ($is_vip && $marketGoods['vip_wholesale_price']>0 && $marketGoods['price'] && $marketGoods['price']>0) {
                    $marketOrder['o_price'] = $marketGoods['price'];
                }
                $marketOrder['price'] = $min_price;
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

                $marketOrder['username'] = isset($this->user_session['nickname']) && $this->user_session['nickname'] ? $this->user_session['nickname'] : '';
                $marketOrder['userphone'] =isset($this->user_session['phone']) && $this->user_session['phone'] ? $this->user_session['phone'] : '';
                $marketOrder['address'] = $address;
                $marketOrder['desc'] = $note;
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


                $marketOrder['type'] = 1;
                if ($order_id>0) {
                    $marketOrder['integral_mall_goods_order_id'] = $order_id;
                }

                if ($marketOrderOld) {
                    if ($market_order_id = M('Market_order')->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id, 'goods_id' => $virtual_goods_id, 'mer_id' => $mer_id, 'status' => 10))->save($marketOrder)) {
                        $market_order_id = $marketOrderOld['order_id'];
                        //删除已有的属性与规格的数据
                        $where['order_id'] = $market_order_id;
                        M('Market_order_properties')->where($where)->delete();
                        $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                        foreach ($spec_list as $spec) {
                            M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $market_order_id))->delete();
                        }
                        M('Market_order_spec')->where($where)->delete();
                    }
                } else {
                    $market_order_id = M('Market_order')->add($marketOrder);
                }
                if ($market_order_id) {
                    if ($marketGoods['is_properties']) {
                        $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
                        foreach ($properties as $pro_data) {
                            $pro_data['order_id'] = $market_order_id;
                            M('Market_order_properties')->add($pro_data);
                        }
                    }
                    if ($marketGoods['spec_value']) {
                        $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $marketGoods['goods_id']))->select();
                        foreach ($spec_list as $spec) {
                            $spec['order_id'] = $market_order_id;
                            if (M('Market_order_spec')->add($spec)) {
                                $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    $spec_value['order_id'] = $market_order_id;
                                    M('Market_order_spec_value')->add($spec_value);
                                }
                            }
                        }
                    }
                } else {
                    $this->error_tips('兑换失败！');
                }
            }



            if ($order_id > 0) {
                $save = D('Merchant_integral_mall_goods_order')->where(array('order_id' => $order_id))->data($order_data)->save();
                if (!$save) {
                    $this->error_tips('订单保存失败');
                } elseif (IS_AJAX) {
                    $this->success(array('order_id' => $order_id));
                } else {
                    redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'integral_mall')));
                }
            } else if ($order_id = D('Merchant_integral_mall_goods_order')->add($order_data)) {
                D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 0, 'from_uid' => $uid, 'note' => '用户下单'));
                if ($market_order_id>0) {
                    M('Market_order')->where(array('order_id' => $market_order_id))->setField('integral_mall_goods_order_id', $order_id);
                }
                if ($this->user_session['openid']) {
                    if ($order) {
                        $keyword2 = $order['goods_name'] . ': ' . $payment_pure_integral . '×' . $num;
                    } else {
                        $keyword2 = $detail['goods_name'] . ': ' . $payment_pure_integral . '×' . $num;
                    }
                    // 订单详情页面
                    $href = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='. $order_id . '&mer_id=' . $order_data['mer_id'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '您好，您的订单已生成', 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $keyword2, 'remark' => '您的该次积分商城下单成功，感谢您的使用！'), $order_data['mer_id']);
                }

                $sms_data = array('mer_id' => $mer_id, 'goods_id' => $goods_id, 'type' => 'integral_mall');
                if ($this->config['sms_shop_place_order'] == 1 || $this->config['sms_shop_place_order'] == 3) {
                    $sms_data['uid'] = $uid;
                    $sms_data['mobile'] = $order_data['userphone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = '您' . date("Y-m-d H:i:s") . '在【' . $merchant['name'] . '】商家积分商城中下了一个订单，订单号：' . $orderid;
                    Sms::sendSms($sms_data);
                }
                if ($this->config['sms_shop_place_order'] == 2 || $this->config['sms_shop_place_order'] == 3) {
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $merchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = '顾客【' . $order_data['username'] . '】在' . date("Y-m-d H:i:s") . '时下了一个商家积分商城订单，订单号：' . $orderid . '请您注意查看并处理!';
                    Sms::sendSms($sms_data);
                }

                if(IS_AJAX){
                    $this->success(array('order_id' => $order_id,'freight_charge'=>$order_data['freight_charge']));
                }else{
                    redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'integral_mall')));
                }
            } else {
                $this->error_tips('订单保存失败');
            }
        } else {
            $this->error_tips('不合法的提交');
        }

    }


    public function storeCate()
    {
        $mer_id = I('mer_id',0,'intval');
        if(empty($mer_id)){
            $this->error('参数错误！');
        }

        $cat_id = I('cat_id',0,'intval');//分类id
        $this->assign('cat_id',$cat_id);

        $model = D('Merchant_integral_mall_category');
        $category = $model->get_category_list($mer_id,'cat_id,cat_name');
        $this->assign('category',$category);

        $this->display();

    }
    public function storeList()
    {
        $mer_id = I('mer_id',0,'intval');
        if(empty($mer_id)){
            $this->error_tips('参数错误！');
        }
        $this->assign('user',$this->user_session);
        $score = $this->get_user_score($mer_id,$this->user_session['uid']);
        $this->assign('score',$score);

        // 分类页面
        $this->display();

    }
    public function my_center()
    {

        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }

        $mer_id = I('mer_id',0,'intval');
        if(empty($mer_id)){
            $this->error('参数错误！');
        }

        $this->assign('user',$this->user_session);
        $score = $this->get_user_score($mer_id,$this->user_session['uid']);
        $this->assign('score',$score);

        // 个人中心页面
        $this->display();
    }

    //订单列表页
    public function order_list(){
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }

        $mer_id = I('mer_id',0,'intval');
        if(empty($mer_id)){
            $this->error('参数错误！');
        }

        $this->display();
    }

    public function ajax_shop_order_list(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！');
        }
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $mer_id = intval($_GET['mer_id']);
        $where = 'mer_id='.$mer_id;
        $where .= " AND is_del=0 AND uid={$this->user_session['uid']}";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
        if ($status == -1) {		//待付款
            $where .= " AND paid=0";
        } elseif ($status == 1) {	//待发货
            $where .= " AND is_pick_in_store=1 && paid=1 AND status=1";
        } elseif ($status == 2) {	//待收货
            $where .= " AND paid=1 AND ((is_pick_in_store=1 AND status=2) OR(is_pick_in_store=0 AND status=1))";
        }else if($status==3){	//收货成功 或者自提
            $where.=' and paid=1 and (status=3 OR status=5)';
        }else if($status==4){	//已退款
            $where.=' and paid=1 and status=4';
        }elseif($status==6){	//订单取消（未支付）
            $where.=' and paid=0 and status=6';
        }

//        if($status==6){		//退款申请已处理且无正在申请中的
//            $sql=D('Shop_order')->alias('ord')->join('inner join '.C('DB_PREFIX').'shop_order_refund re on ord.order_id=re.order_id')->where($where)->field('ord.*,sum(case when re.status=0 then 1 else 0 end) as apply_num')->group('ord.order_id')->having('apply_num=0')->buildSql();
//            $order_list=M('')->table($sql.' as t')->order('order_id desc')->select();
//        }else{
//            $order_list = D("Shop_order")->field(true)->where($where)->order('order_id DESC')->select();
//        }
        $order_list = D("Merchant_integral_mall_goods_order")->field(true)->where($where)->order('order_id DESC')->select();
        $list = $this->deal_order_list($order_list);
        //echo M()->getLastSql();exit;
//        $get_goods=I('get.get_goods',0,'intval');
//        if($get_goods==1){
//            foreach ($order_list as $k=>$st) {
//                $st['goods_list']=D('Shop_order')->get_good_details($st['order_id']);
//                $order_list[$k]=$st;
//
//                $store_ids[] = $st['store_id'];
//            }
//        }else{
//            foreach ($order_list as $st) {
//                $store_ids[] = $st['store_id'];
//            }
//        }
//        $m = array();
//        if ($store_ids) {
//            $store_image_class = new store_image();
//            $merchant_list = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
//            foreach ($merchant_list as $li) {
//                if($li['logo']){
//                    $li['image'] = file_domain().$li['logo'];
//                }else{
//                    $images = $store_image_class->get_allImage_by_path($li['pic_info']);
//                    $li['image'] = $images ? array_shift($images) : array();
//                }
//                unset($li['status']);
//                $m[$li['store_id']] = $li;
//            }
//        }
//        $list = array();
//        foreach ($order_list as $ol) {
//            if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
//                $list[] = array_merge($ol, $m[$ol['store_id']]);
//            } else {
//                $list[] = $ol;
//            }
//        }
//
//        foreach($list as $key=>$val){
//            if($val['paid']==1 && $val['status']==3){
//                $where=array();
//                $where['order_id']=$val['order_id'];
//                $goods_type_num=M('Shop_order_detail')->where($where)->count(1);
//                $list[$key]['goods_type_num']=$goods_type_num;
//            }
//            $list[$key]['order_url'] = U('order_detail_new', array('order_id' => $val['order_id']));
//        }

        if(!empty($list)){
            exit(json_encode(array('status'=>1,'order_list'=>$list)));
        }else{
            exit(json_encode(array('status'=>0,'order_list'=>$list)));
        }
    }

    protected function deal_order_list($data, $is_goods_img=true){
        if($data){
            foreach ($data as $Key => $Gift) {
                $gift_pc_pic = str_replace(',', '/', $Gift['pc_pic']);
                $gift_wap_pic = str_replace(',', '/', $Gift['wap_pic']);
                $data[$Key]['pc_pic_list'] = explode(';', $gift_pc_pic);
                $data[$Key]['wap_pic_list'] = explode(';', $gift_wap_pic);//以前的逻辑，不要动，以免影响以前的业务

                if(!empty($Gift['wap_pic'])){
                    $merchant_image_class = new merchant_image();
                    $goods_image_class    = new goods_image();
                    $wap_pic_list = array();
                    foreach($data[$Key]['wap_pic_list'] as $key=>$pic){
                        if (stripos($pic,'upload/') !== false || $is_goods_img) {

                            $wap_pic_list[$key]['title'] = $pic;
                            $tmp_pic = $goods_image_class->get_image_by_path($pic);
                            $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                        } else {

                            $wap_pic_list[$key]['title'] = $pic;
                            $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                            $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                        }
                    }
                    $data[$Key]['wap_pic_arr'] = $wap_pic_list;
                    $data[$Key]['wap_pic_first'] = trim($wap_pic_list[0]['url'],'/');//只需要一张图片
                    $data[$Key]['order_url'] = U('order_detail_new',array('order_id'=>$Gift['order_id']));//只需要一张图片
                }
            }
        }
        return $data;
    }

    public function ajax_shop_order_del(){
        $db = M('Merchant_integral_mall_goods_order');
        $where = array();
        $where['order_id'] = intval($_GET['order_id']);
        //$where['uid'] = $this->user_session['uid'];
        $now_order =$db->where($where)->find();
        if(empty($now_order)){
            exit(json_encode(array('status'=>0,'msg'=>'当前订单不存在！')));
        }
        $data = array();
        $data['is_del'] = 1;
        if($db->where($where)->data($data)->save()){
            exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
        }
    }

    /**
     * @desc 获取用户商家积分
     * @param $mer_id
     * @param $uid
     * @return array
     */
    protected function get_user_score($mer_id,$uid){
        $db = M('Card_userlist');
        $where = array();
        $where['mer_id'] = $mer_id;
        $where['uid'] = $uid;
        return $db->where($where)->field(true)->find();
    }


    /**
     * 订单详情
     */
    public function order_detail_new()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order_info = D("Merchant_integral_mall_goods_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
        if ($order_info) {
            $merchant_image_class = new merchant_image();
            $goods_image_class = new goods_image();
            $is_goods_img = true;
            if(!empty($order_info['wap_pic'])){
                $wap_pic_arr = explode(';',$order_info['wap_pic']);
                $wap_pic_list = array();
                foreach($wap_pic_arr as $key=>$pic){
                    if (stripos($pic,'upload/') !== false || $is_goods_img) {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $goods_image_class->get_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    } else {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    }
                }
                $order_info['wap_pic_list'] = $wap_pic_list;
                $image_wap = reset($wap_pic_list);
                $order_info['wap_image'] = $image_wap['url'];
            }

            $user = M('User')->where(array('uid' => $order_info['uid']))->field('phone')->find();

            // 规格信息
            $spec_value = $order_info['spec_value'];
            if ($spec_value) {
                $spec_list = $spec_value['list'];
                $spec_info = $spec_list[$order_info['spec_index']];
            } else {
                $spec_info = array();
            }
            $this->assign('spec_info', $spec_info);

            if ($order_info['is_pick_in_store'] == 1 && $order_info['freight_charges'] == 1 && $order_info['freight_charge'] > 0) {
                $real_money = floatval($order_info['total_money']) - floatval($order_info['freight_charge']);
            } else {
                $real_money = floatval($order_info['total_money']);
            }
            $real_money = strval($real_money);

            $arr['order_details'] = array(
                'orderid' => $order_info['orderid'],
                'order_id' => $order_info['order_id'],
                'goods_id' => $order_info['goods_id'],
                'mer_id' => $order_info['mer_id'],
                'spec_index' => $order_info['spec_index'],
                'goods_name' => $order_info['goods_name'],
                'real_money' => $real_money,
                'username' => $order_info['username'],
                'userphone' => $order_info['userphone'],
                'create_time' => $order_info['create_time'] ? date('Y-m-d H:i:s',$order_info['create_time']) : '',
                'pay_time' => $order_info['pay_time'] ? date('Y-m-d H:i:s',$order_info['pay_time']) : '',
                'is_pick_in_store' => $order_info['is_pick_in_store'],// 配送方式（0 到店自提 1 供货商家快递发货）
                'address' => $order_info['address'],
                'deliver_str' => $order_info['deliver_str'],
                'note' => isset($order_info['desc']) ? nl2br(strip_tags(htmlspecialchars_decode($order_info['desc']))) : '',
                'send_note' => isset($order_info['send_note']) ? nl2br(strip_tags(htmlspecialchars_decode($order_info['send_note']))) : '',
                'pay_status' => $order_info['pay_status_print'],
                'pay_type_str' => $order_info['pay_type_str'],
                'status_str' => $order_info['status_str'],
                'card_give_money' => strval(floatval($order_info['merchant_balance_give'])),//会员卡赠送余额
                'merchant_balance' => strval(floatval($order_info['merchant_balance_pay'])),//商家余额
                'balance_pay' => strval(floatval($order_info['balance_pay'])),//平台余额
                'payment_money' => strval(floatval($order_info['payment_money'])),//在线支付的金额
                'status' => $order_info['status'],
                'paid' => $order_info['paid'],
                'register_phone' => $user['phone'],// 注册时的用户手机号
                'lat' => $order_info['latitude'],
                'lng' => $order_info['longitude'],
                'num' => $order_info['num'],
                'payment_pure_integral' => strval(floatval($order_info['payment_pure_integral'])),//单个商品的商家积分
                'total_integral' => strval(floatval($order_info['total_integral'])),//订单商品兑换使用总积分
                'freight_charge' => strval(floatval($order_info['freight_charge'])),//配送费
                'freight_charges' => $order_info['freight_charges'], // 运费承担方 0 用户 1 发布商户
                'total_money' => strval(floatval($order_info['total_money'])),//订单总价
                'pay_money' => strval(floatval($order_info['pay_money'])),// 线上支付
                'log_list' => D('Merchant_integral_mall_log')->where(array('order_id' => $order_info['order_id']))->order('id DESC')->find(),
                'spec_value' => $order_info['spec_value'],
                'spec_info' => $spec_info,
                'pay_type' => isset($order_info['pay_type']) ? $order_info['pay_type'] : '',
                'arrival_time' => $order_info['arrival_time'] ? date('Y-m-d H:i:s',$order_info['arrival_time']) : '',
                'send_time' => $order_info['send_time'] ? date('Y-m-d H:i:s',$order_info['send_time']) : '',
                'express_number' => isset($order_info['express_number']) ? $order_info['express_number'] : '',
                'express_name' => isset($order_info['express_name']) ? $order_info['express_name'] : '',
                'express_code' => isset($order_info['express_code']) ? $order_info['express_code'] : '',
                'express_id'=>$order_info['express_id'],
                'is_del'=>$order_info['is_del'],
                'fetch_number'=>$order_info['fetch_number'],
                'pic_image'=>$order_info['pic_image'],
                'wap_image'=>$order_info['wap_image'],
            );
            $this->assign($arr);
            $this->display();
        } else {
            $this->error_tips('订单信息错误！');
        }
    }


    /**
     * 订单状态列表
     */
    public function status_new()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = D("Merchant_integral_mall_goods_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
        if ($order) {
            $status = D('Merchant_integral_mall_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
            $statusCount = D("Merchant_integral_mall_log")->where(array('order_id' => $order_id))->count();

            $this->assign('statusCount', $statusCount);

            $this->assign('status', $status);
            $this->assign('order_id', $order_id);
            $this->assign('order', $order);
            $this->display();
        } else {
            $this->error_tips('错误的订单信息！');
        }
    }


    /**确认收货*/
    public function finish_order() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $where = array('order_id' => $order_id, 'uid' => $this->user_session['uid']);
        $order = D('Merchant_integral_mall_goods_order')->field(true)->where($where)->find();
        if (empty($order)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单信息不正确')));
        }
        if ($order['status'] >= 3) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单已确认，请不要重复确认')));
        }
        if ($order['status'] != 2) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单当前不能确认收货')));
        }
        $data = array('status' => 3);
        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Merchant_integral_mall_goods_order')->where($where)->save($data)) {

            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 3, 'from_uid' => $this->user_session['uid'], 'note' => '用户确认收货'));

            // 如果存在对应批发订单，改为已收货
            $databaseMarketOrder = D('Market_order');
            $marketOrder = $databaseMarketOrder->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id))->find();
            if ($marketOrder) {
               if ($databaseMarketOrder->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id))->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))){
                    $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                    $merchant = M('Merchant')->where(array('mer_id' => $marketOrder['mer_id']))->find();
                    $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $sellmerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                    Sms::sendSms($sms_data);
                }
            }
            // 用户确认收货-计算给予商家结算
            D('Merchant_integral_mall_goods_order')->order_settlement($order);

            exit(json_encode(array('errcode' => 0, 'msg' => 'ok', 'url' => U('Integral_mall/order_detail_new', array('order_id' => $order_id)))));
        } else {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单状态修改失败，稍后重试')));
        }
    }

    /**确认自提*/
    public function finish_take() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $where = array('order_id' => $order_id, 'uid' => $this->user_session['uid']);
        $order = D('Merchant_integral_mall_goods_order')->field(true)->where($where)->find();
        if (empty($order)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单信息不正确')));
        }
        if ($order['status'] == 5) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单已确认自提，请不要重复确认')));
        }
        if ($order['status'] != 1) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单当前不能确认自提')));
        }
        $data = array('status' => 5);
        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Merchant_integral_mall_goods_order')->where($where)->save($data)) {

            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 5, 'from_uid' => $this->user_session['uid'], 'note' => '用户确认自提'));

            // 如果存在对应批发订单，改为已收货
            $databaseMarketOrder = D('Market_order');
            $marketOrder = $databaseMarketOrder->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id))->find();
            if ($marketOrder) {
                if ($databaseMarketOrder->where(array('type'=>1,'integral_mall_goods_order_id' => $order_id))->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))){
                    $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                    $merchant = M('Merchant')->where(array('mer_id' => $marketOrder['mer_id']))->find();
                    $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $sellmerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                    Sms::sendSms($sms_data);
                }
            }

            // 用户确认自提-计算给予商家结算（暂无结算）
             D('Merchant_integral_mall_goods_order')->order_settlement($order);

            exit(json_encode(array('errcode' => 0, 'msg' => 'ok', 'url' => U('Integral_mall/order_detail_new', array('order_id' => $order_id)))));
        } else {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单状态修改失败，稍后重试')));
        }
    }

    // 支付 减积分
    public function pay_check(){
        $order_id = $_POST['order_id'];

        $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
        $login_url = U('Login/index',$location_param);

        if (!$this->user_session) {
            exit(json_encode(array('errcode' => 2, 'msg' => L_('请先进行登录！'),'url'=>$login_url)));
        }

        $now_user = D('User')->get_user($this->user_session['uid']);      
        if (!$now_user) {
            exit(json_encode(array('errcode' => 2, 'msg' => L_('用户不存在'),'url'=>$login_url)));
        }

        $now_order =  D('Merchant_integral_mall_goods_order')->where(array('order_id' => $order_id,'uid'=>$this->user_session['uid']))->find();
        if (!$now_order) {
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单不存在'))));
        }

        if($now_order['paid']==1){
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单已支付过'))));
        }

        if($now_order['paid']!=0){
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单状态异常'))));
        }

        $uid = $now_order['uid'];
        $mer_id = $now_order['mer_id'];
        $now_user_card = M('Card_userlist')->where(array('uid'=>$uid,'mer_id'=> $mer_id))->find();
        if(!empty($now_user_card) && $now_user_card['status']==0){
            exit(json_encode(array('errcode' => 1, 'msg' => L_('您的会员卡不能使用！'))));
        }

        if ($now_order['total_integral'] > $now_user_card['card_score']) {
            exit(json_encode(array('errcode' => 1, 'msg' => L_('您的会员卡积分当前不足！') . L_('剩余商家积分') . $now_user_card['card_score'])));
        }

        $returnArr['card_score'] = $now_user_card['card_score'];
        $returnArr['total_integral'] = $now_order['total_integral'];
        exit(json_encode(array('errcode' => 0, 'msg' => L_('可以兑换'),'info'=>$returnArr)));
    }

    // 支付 减积分
    public function go_pay(){
        $order_id = $_POST['order_id'];

        if (!$this->user_session) {
            exit(json_encode(array('errcode' => 2, 'msg' => L_('请先进行登录！'))));
        }

        $now_user = D('User')->get_user($this->user_session['uid']);      
        if (!$now_user) {
            exit(json_encode(array('errcode' => 2, 'msg' => L_('用户不存在'))));
        }

        $now_order =  D('Merchant_integral_mall_goods_order')->where(array('order_id' => $order_id))->find();
        if (!$now_order) {
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单不存在'))));
        }

        if($now_order['paid']==1){
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单已支付过'))));
        }

        if($now_order['paid']!=0){
            exit(json_encode(array('errcode' => 1, 'msg' => L_('订单状态异常'))));
        }

        $order_param['order_id'] = $now_order['order_id'];
        $order_param['is_mobile'] = 1;
        $order_param['pay_money'] = 0;
        $order_param['pay_type'] = '';
        
        $return = D('Merchant_integral_mall_goods_order')->after_pay($order_param);

        if ($return['error']) {
            exit(json_encode(array('errcode' => 1, 'msg' => $return['msg'])));
        }else{
            exit(json_encode(array('errcode' => 0, 'msg' => L_('兑换成功'), 'url' => $return['url'])));
        }
    }

}
?>