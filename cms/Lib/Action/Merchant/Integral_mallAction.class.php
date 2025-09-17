<?php
/*
 * 商家积分商城
 *
 */

class Integral_mallAction extends BaseAction
{
    public function index(){
        $keyword=I('get.keyword','','trim');
        $searchtype=I('get.searchtype','','trim');
        $where=array();
        $mer_id=$_SESSION['merchant']['mer_id'];
        $where['mer_id']=$mer_id;
        if(!empty($keyword)){
            switch($searchtype){
                case 'goods_id':
                    $where['goods_id'] = $keyword + 0;
                    break;
                case 'goods_name':
                    $where['goods_name'] = array('like','%'.$keyword.'%');
                    break;
            }
        }
        $searchstatus=I('searchstatus',0,'intval');
        if($searchstatus>0){
            switch ($searchstatus) {
                case '1':
                    $where['status']=1;
                    break;
                case '2':
                    $where['status']=0;
                    break;
            }
        }

        $database_merchant_integral_mall_goods = D('Merchant_integral_mall_goods');
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
        $where['is_del'] = 0;

        $data = $database_merchant_integral_mall_goods->Merchant_integral_mall_goods_page_list($where,true,'sort desc, goods_id desc');

        $where_cat['mer_id'] = $mer_id;
        $cat_list = $database_merchant_integral_mall_category->where($where_cat)->getField('cat_id,cat_name');

        $this->assign('cat_list',$cat_list);

        $info=$data['list'];
        foreach ($info['list'] as $k => $v) {
            $cat_name='';
            if($v['cat_fid']>0){
                $cat_name.=$cat_list[$v['cat_fid']];
            }
            if($v['cat_id']){
                $cat_name.='-'.$cat_list[$v['cat_id']];
            }
            $info['list'][$k]['cat_name']=$cat_name;
        }

        $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_add_integral_mall')->find();
        $is_add_integral_mall = $now_merchant['is_add_integral_mall']?$now_merchant['is_add_integral_mall']:0;
        $this->assign('is_add_integral_mall',$is_add_integral_mall);

        $this->assign('info',$info);
        $this->display();
    }


    //编辑商品-真实批发
    public function goods_save(){
        $goods_id=I('goods_id',0,'intval');
        $order_id=I('order_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $mer_id=$_SESSION['merchant']['mer_id'];

        if ($goods_id<=0 && $order_id<=0 && $type=='') {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_add_integral_mall')->find();
            $is_add_integral_mall = $now_merchant['is_add_integral_mall']?$now_merchant['is_add_integral_mall']:0;
            if ($is_add_integral_mall!=1) {
                $this->error(L_('当前您不能自主添加商家积分商城商品，如需上架请联系管理员！'));
            }
        }

        if(IS_POST){
            $data=$_POST;
            if (!$goods_id) {
                $data['deliver_type'] = 1; // 到点自提
                if ($type=='market') {
                    $data['goods_source_type'] = 2; // 商户真实批发
                } else {
                    $data['goods_source_type'] = 1; // 商户创建
                }
            }
            if ($data['is_spec'] && $data['market_order_id']>0) {
                $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
                $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
                if ($is_publish_integral_mall != 1) {
                    $this->error(L_('当前您不能上架到商家积分商城，如需上架请联系管理员！'));
                }

                $where['order_id']=$data['market_order_id'];
                $where['mer_id']=$mer_id;
                $where['status']=3;
                $market_order = M('Market_order')->where($where)->find();
                $return = D('Market_order')->format_spec_value($market_order['spec_value'], $market_order['goods_id'], $data['market_order_id']);

                $spec_list = isset($return['spec_list']) ? $return['spec_list'] : '';
                $list = isset($return['list']) ? $return['list'] : '';

//                $data['freight_type'] = $market_order['freight_type']>0?$market_order['freight_type']:1;
                // 真实批发和自建积分商城商品，计费方式默认为0
                $data['freight_type'] = 0;
                $data['goods_volume'] = $market_order['goods_volume']>0?$market_order['goods_volume']:0;
                $data['goods_weight'] = $market_order['goods_weight']>0?$market_order['goods_weight']:0;
                if ($market_order['unit']) {
                    $data['unit'] = $market_order['unit'];
                }

                // 规格-所需积分
                $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                unset($data['payment_pure_integrals']);
                // 规格-库存数量
                $skus = $data['skus'] ? $data['skus'] : array();
                unset($data['skus']);
                // 规格-每人限制兑换数量
                $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                unset($data['exchange_limit_nums']);
                // 规格-已兑换人数
                $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                unset($data['exchanged_nums']);

                if ($spec_list && $list) {
                    $payment_pure_integral = 0;
                    $g_where['market_goods_id']=$data['market_goods_id'];
                    $g_where['mer_id']=$mer_id;
                    $mall_goods = $model->goods_detail($g_where);
                    $detail = $mall_goods['detail'];
                    $goods_id = $detail['goods_id'];
                    if ($detail && $detail['spec_val']) {
                        $list_old = $detail['spec_val']['list'];
                    } else {
                        $list_old = array ();
                    }
                    if ($detail['market_order']) {
                        $market_order = explode(',', $detail['market_order']);
                    } else {
                        $market_order = array();
                    }
//                    if (in_array($data['market_order_id'], $market_order)) {
//                        $this->error(L_('该订单商品已上架到商家积分商城，请不要重复上架！'));
//                    }
                    $market_order[] = $data['market_order_id'];
                    $data['market_order'] = implode(',', $market_order);

                    $sku = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->error(L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                        if ($skus[$index] > $val['stock_num']) {
                            $this->error(L_('当前您不能上架到商家积分商城，库存不能大于大于当前购买数量！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        if ($exchanged_nums[$index]>0) {
                            $exchanged_num += $exchanged_nums[$index];
                        }
                        if ($list_old && $list_old[$key]) {
                            $val['stock_num'] += $list_old[$key]['stock_num'];
                            $val['sell_num'] = $list_old[$key]['sell_num']>0 ? $list_old[$key]['sell_num'] : 0;
                            $val['exchanged_num'] += $list_old[$key]['exchanged_num'];
                            $val['sku'] += $list_old[$key]['sku'];
                        }
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } elseif ($goods_id>0 && ($data['payment_pure_integrals'] || $data['skus'] || $data['exchange_limit_nums'] || $data['exchange_limit_nums'])) {
                $g_where['mer_id']=$mer_id;
                $g_where['goods_id']=$goods_id;
                $mall_goods = $model->goods_detail($g_where);
                $detail = $mall_goods['detail'];
                $spec_val = $detail['spec_val'];

                $spec_list = isset($spec_val['spec_list']) ? $spec_val['spec_list'] : '';
                $list = isset($spec_val['list']) ? $spec_val['list'] : '';

                if ($spec_val && $spec_list && $list) {
                    // 规格-所需积分
                    $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                    unset($data['payment_pure_integrals']);
                    // 规格-库存数量
                    $skus = $data['skus'] ? $data['skus'] : array();
                    unset($data['skus']);
                    // 规格-每人限制兑换数量
                    $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                    unset($data['exchange_limit_nums']);
                    // 规格-已兑换人数
                    $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                    unset($data['exchanged_nums']);

                    $sku = 0;
                    $payment_pure_integral = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->error(L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($skus[$index] > floatval($val['stock_num'])) {
                            $this->returnCode(1,'', L_('库存不能大于大于当前库存！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        if (!$val['sell_num'] || $val['sell_num'] < 0) {
                            $val['sell_num'] = 0;
                        }
                        if ($exchanged_nums[$index]>0) {
                            $exchanged_num += $exchanged_nums[$index];
                        }
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } else {
                $data['spec_val'] = '';
            }


            $data['mer_id']=$mer_id;
            if($goods_id>0){
                $where=array();
                $where['goods_id']=$goods_id;
                unset($data['goods_id']);
                unset($data['goods_source_type']);
                $res=$model->merchant_integral_mall_goods_edit($where,$data);
            }else{
                unset($data['goods_id']);
                $data['status']=1;
                $res=$model->merchant_integral_mall_goods_add($data);
            }
            if(!$res){
                $this->error(L_('数据处理有误！'));
            }else{
                if($res['status']){
                    $this->success($res['msg'],U('Integral_mall/index'));
                }else{
                    $this->error($res['msg']);
                }
            }
        }else{
            $cate_list = $this->_get_integral_mall_category(0);
            $this->assign('cate_list',$cate_list);
            if($goods_id>0){
                $where=array();
                $where['goods_id']=$goods_id;
                $where['mer_id']=$mer_id;
                $data=$model->goods_detail($where);
                if(!$data){
                    $this->error(L_('数据处理有误！'));
                }else{
                    $info=$data['detail'];
                    if ($info && $info['spec_val']) {
                        if ($info['spec_val']) {
                            $info['spec_list'] = $info['spec_val']['spec_list'];
                            $info['list'] = $info['spec_val']['list'];
                        }
                    }
                    $cate_list2 = $this->_get_integral_mall_category($info['cat_fid']);
                    // 真实批发和自建积分商城商品，计费方式默认为0
                    $info['freight_type'] = 0;
                    $this->assign('info',$info);
                    $this->assign('cate_list2',$cate_list2);
                }
            }

            if ($type && $type=='market' && $order_id>0) {
                $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
                $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
                if ($is_publish_integral_mall != 1) {
                    $this->error(L_('当前您不能上架到商家积分商城，如需上架请联系管理员！'));
                }
                $where=array();
                $where['order_id']=$order_id;
                $where['mer_id']=$mer_id;
                $where['status']=3;
                $market_order = M('Market_order')->where($where)->find();

                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $market_order['image']);
                $pc_pic_list = array();
                $wap_pic_list = array();
                foreach ($tmp_pic_arr as $key => $value) {
                    $pc_pic_list[$key]['title'] = $wap_pic_list[$key]['title'] = $value;
                    $tmp_pic = $goods_image_class->get_image_by_path($value);
                    $pc_pic_list[$key]['url'] = $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                }
                $return = D('Market_order')->format_spec_value($market_order['spec_value'], $market_order['goods_id'], $order_id);

                $market_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $market_order['list'] = isset($return['list']) ? $return['list'] : '';



                $g_where['market_goods_id'] = $market_order['goods_id'];
                $g_where['mer_id'] = $mer_id;
                $mall_goods = $model->goods_detail($g_where, true, true);
//                $detail = $mall_goods['detail'];
                $detail = array();
                if ($detail && $detail['spec_val']) {
                    $list_old = $detail['spec_val']['list'];
                } else {
                    $list_old = array ();
                }

                foreach ($market_order['list'] as $key=>&$val) {
                    if ($list_old[$key]['payment_pure_integral']) {
                        $val['payment_pure_integral'] = $list_old[$key]['payment_pure_integral'];
                    }
                    if ($list_old[$key]['exchange_limit_num']) {
                        $val['exchange_limit_num'] = $list_old[$key]['exchange_limit_num'];
                    } else {
                        $val['exchange_limit_num'] = 0;
                    }
                }

                $info = array(
                    'market_goods_id' => $market_order['goods_id'],
                    'market_order_id' => $market_order['order_id'],
                    'goods_name' => $market_order['name'],
                    'sku' => $market_order['num'],
                    'wap_pic_list' => $wap_pic_list,
                    'pc_pic_list' => $pc_pic_list,
                    'spec_list' => $market_order['spec_list'],
                    'list' => $market_order['list'],
                    'is_spec' =>  $market_order['spec_list'] ? true : false,
                    'status' =>  1,
                );

                if ($detail && $detail['cat_fid'] > 0) {
                    $info['cat_fid'] = $detail['cat_fid'];
                }
                if ($detail && $detail['cat_id'] > 0) {
                    $info['cat_id'] = $detail['cat_id'];
                }
                if ($detail && $detail['deliver_type'] > 0) {
                    $info['deliver_type'] = $detail['deliver_type'];
                }
                if ($detail && $detail['exchange_limit_num'] > 0) {
                    $info['exchange_limit_num'] = $detail['exchange_limit_num'];
                }
                if ($detail && $detail['pc_pic_list'] > 0) {
                    $info['pc_pic_list'] = $detail['pc_pic_list'];
                }
                if ($detail && $detail['wap_pic_list'] > 0) {
                    $info['wap_pic_list'] = $detail['wap_pic_list'];
                }
                if ($detail && $detail['intro']) {
                    $info['intro'] = $detail['intro'];
                }
                if ($detail && $detail['invoice_content']) {
                    $info['invoice_content'] = $detail['invoice_content'];
                }
                if ($market_order && $market_order['des']) {
                    $info['goods_content'] = replace_file_domain_content($market_order['des']);
                }
                if ($detail && $detail['goods_content']) {
                    $info['goods_content'] = replace_file_domain_content($detail['goods_content']);
                }
                if ($detail && $detail['is_new']) {
                    $info['is_new'] = $detail['is_new'];
                }
                if ($detail && $detail['status']) {
                    $info['status'] = $detail['status'];
                }
                if ($market_order && $market_order['freight_type']>0) {
                    $info['freight_type'] = $market_order['freight_type'];
                } elseif ($detail && $detail['freight_type']>0) {
                    $info['freight_type'] = $detail['freight_type'];
                }
                // 真实批发和自建积分商城商品，计费方式默认为0
                $info['freight_type'] = 0;
                if ($market_order && $market_order['goods_weight']>0) {
                    $info['goods_weight'] = $market_order['goods_weight'];
                } elseif ($detail && $detail['goods_weight']>0) {
                    $info['goods_weight'] = $detail['goods_weight'];
                }
                if ($market_order && $market_order['goods_volume']>0) {
                    $info['goods_volume'] = $market_order['goods_volume'];
                } elseif ($detail && $detail['goods_volume']>0) {
                    $info['goods_volume'] = $detail['goods_volume'];
                }


                $this->assign('info',$info);
            }

            $this->display();
        }
    }


    //虚拟批发-编辑商品
    public function virtual_wholesale_save(){
        $goods_id=I('goods_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $d_market_goods=D('Market_goods');
        $mer_id=$this->merchant_session['mer_id'];
        if(empty($mer_id)){
            redirect(U('Login/index'));exit;
        }

        if(IS_POST){
            $data=$_POST;
            $data['goods_source_type'] = 3;
            $data['deliver_type'] = 0; // 快递配送
            if ($data['virtual_wholesale_goods_id']>0 || $data['market_goods_id']>0) {
                $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
                $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
                if ($is_publish_integral_mall != 1) {
                    $this->error(L_('当前您不能虚拟上架到商家积分商城，如需上架请联系管理员！'));
                }
                $virtual_goods_id = $data['virtual_wholesale_goods_id']>0 ? $data['virtual_wholesale_goods_id'] : $data['market_goods_id'];
                if (!$data['virtual_wholesale_goods_id']) {
                    $data['virtual_wholesale_goods_id'] = $virtual_goods_id;
                }
                $where['goods_id']=$virtual_goods_id;
                $where['status']=1;
                $market_goods = $d_market_goods->where($where)->find();
                if ($market_goods['mer_id'] == $mer_id) {
                    $this->error(L_('你不能虚拟商家自己的批发市场商品！'));
                }
                $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);

                $spec_list = isset($return['spec_list']) ? $return['spec_list'] : '';
                $list = isset($return['list']) ? $return['list'] : '';
                $data['wholesale_price'] = $market_goods['price']>0?$market_goods['price']:1;
                $data['vip_wholesale_price'] = $market_goods['vip_wholesale_price']>0?$market_goods['vip_wholesale_price']:1;
                $data['freight_type'] = $market_goods['freight_type']>0?$market_goods['freight_type']:1;
                $data['goods_volume'] = $market_goods['goods_volume']>0?$market_goods['goods_volume']:0;
                $data['goods_weight'] = $market_goods['goods_weight']>0?$market_goods['goods_weight']:0;

                if ($market_goods['express_template_id'] > 0) {
                    $data['express_template_id'] = $market_goods['express_template_id'];
                    $where_express_1['id'] = $market_goods['express_template_id'];
                    $express_info = M('Market_express_template')->where($where_express_1)->find();
                    if (empty($express_info)) {
                        $this->error(L_('运费模板信息错误！'));
                    }
                }
                if ($market_goods['express_fee'] > 0) {
                    $data['express_fee'] = $market_goods['express_fee'];
                }
                if ($market_goods['unit']) {
                    $data['unit'] = $market_goods['unit'];
                }

                // 规格-所需积分
                $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                unset($data['payment_pure_integrals']);
                // 规格-库存数量
                $skus = $data['skus'] ? $data['skus'] : array();
                unset($data['skus']);
                // 规格-每人限制兑换数量
                $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                unset($data['exchange_limit_nums']);
                // 规格-已兑换人数
                $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                unset($data['exchanged_nums']);

                if ($spec_list && $list) {
                    $sku = 0;
                    $payment_pure_integral = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->error(L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                        if ($skus[$index] > $val['stock_num']) {
                            $this->error(L_('库存不能大于大于当前购买数量！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        $exchanged_num += $exchanged_nums[$index];
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['goods_source_type'] = 3;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } elseif ($goods_id>0 && ($data['payment_pure_integrals'] || $data['skus'] || $data['exchange_limit_nums'] || $data['exchange_limit_nums'])) {
                $g_where['mer_id']=$mer_id;
                $g_where['goods_id']=$goods_id;
                $mall_goods = $model->goods_detail($g_where);
                $detail = $mall_goods['detail'];
                $spec_val = $detail['spec_val'];

                $spec_list = isset($spec_val['spec_list']) ? $spec_val['spec_list'] : '';
                $list = isset($spec_val['list']) ? $spec_val['list'] : '';

                if ($spec_val && $spec_list && $list) {
                    // 规格-所需积分
                    $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                    unset($data['payment_pure_integrals']);
                    // 规格-库存数量
                    $skus = $data['skus'] ? $data['skus'] : array();
                    unset($data['skus']);
                    // 规格-每人限制兑换数量
                    $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                    unset($data['exchange_limit_nums']);
                    // 规格-已兑换人数
                    $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                    unset($data['exchanged_nums']);

                    $sku = 0;
                    $payment_pure_integral = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->error(L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                        if ($skus[$index] > ($val['stock_num'] - floatval($val['sell_num']))) {
                            $this->error(L_('库存不能大于大于当前购买数量！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        if (!$val['sell_num'] || $val['sell_num'] < 0) {
                            $val['sell_num'] = 0;
                        }
                        $exchanged_num += $exchanged_nums[$index];
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } else {
                $data['spec_val'] = '';
            }

            $data['mer_id']=$mer_id;
            if($goods_id>0){
                $where=array();
                $where['goods_id']=$goods_id;
                unset($data['goods_id']);
                $res=$model->merchant_integral_mall_goods_edit($where,$data);
            }else{
                $data['status']=1;
                $res=$model->merchant_integral_mall_goods_add($data);
            }
            if(!$res){
                $this->error(L_('数据处理有误！'));
            }else{
                if($res['status']){
                    $this->success($res['msg'],U('Integral_mall/index'));
                }else{
                    $this->error($res['msg']);
                }
            }
        }else{
            $cate_list = $this->_get_integral_mall_category(0);
            $this->assign('cate_list',$cate_list);
            if($goods_id>0 && $type != 'virtual_market'){
                $where=array();
                $where['goods_id']=$goods_id;
                $where['mer_id']=$mer_id;
                $data=$model->goods_detail($where);
                if(!$data){
                    $this->error(L_('数据处理有误！'));
                }else{
                    $info=$data['detail'];
                    // 处理下，如果当前库存大于批发市场库存，同步减少到与批发市场库存一致
                    $virtual_goods_id = $info['virtual_wholesale_goods_id']>0 ? $info['virtual_wholesale_goods_id'] : $info['market_goods_id'];
                    $s_where['goods_id']=$virtual_goods_id;
                    $s_where['status']=1;
                    $market_goods = $d_market_goods->where($s_where)->find();
//                    if (empty($market_goods)) {
//                        $this->error(L_('商品不存在或者下架或者关联店铺不存在了！'));
//                    }

                    if ($info && $info['spec_val']) {
                        $info['spec_list'] = $info['spec_val']['spec_list'];
                        $info['list'] = $info['spec_val']['list'];
                        $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                        $list = isset($return['list']) ? $return['list'] : '';
                        foreach ($info['list'] as $key1=>&$val1) {
                            if ($list[$key1]) {
                                $val1['sku'] = min($list[$key1]['stock_num'], $val1['sku']);
                                if ($list[$key1]['sku']>0) {
                                    $val1['sku'] = min($list[$key1]['stock_num'], $val1['sku']);
                                } elseif ($list[$key1]['sku'] != -1) {
                                    $val1['sku'] = 0;
                                }
                            }
                        }
                    }
                    if ($market_goods['stock_num']>0) {
                        $info['sku'] = min($info['sku'], $market_goods['stock_num']);
                    } elseif ($market_goods['stock_num'] != -1) {
                        $info['sku'] = 0;
                    }

                    $cate_list2 = $this->_get_integral_mall_category($info['cat_fid']);

                    // 虚拟批发直接同步
                    $info['express_template_id'] = $market_goods['express_template_id'];
                    $info['express_fee'] = $market_goods['express_fee'];
//                    if ($info['freight_type']==$market_goods['freight_type'] && $market_goods['express_template_id'] > 0 && $info['express_template_id'] <= 0) {
//                        $info['express_template_id'] = $market_goods['express_template_id'];
//                    }
//                    if ($info['freight_type']==$market_goods['freight_type'] && $market_goods['express_fee'] > 0 && $info['express_fee'] <= 0) {
//                        $info['express_fee'] = $market_goods['express_fee'];
//                    }
                    unset($info['virtual_wholesale_goods_id']);
                    $this->assign('info',$info);
                    $this->assign('cate_list2',$cate_list2);
                }
            } else if ($type && $type=='virtual_market' && $goods_id>0) {
                $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('money,is_publish_integral_mall')->find();
                $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
                if ($is_publish_integral_mall != 1) {
                    $this->error(L_('当前您不能虚拟上架到商家积分商城，如需上架请联系管理员！'));
                }
                $merchant_integral_mall_imprest = floatval($this->config['merchant_integral_mall_imprest']);
                if ($merchant_integral_mall_imprest>0 && floatval($now_merchant['money'])<$merchant_integral_mall_imprest) {
                    $add_money = floatval($merchant_integral_mall_imprest) - floatval($now_merchant['money']);
                    $this->error(L_('您的备用金不足,当前余额X1,还需充值X2，请充值！',array("X1" => $now_merchant['money'], 'X2' => $add_money)));
                }


                $where=array();
                $where['goods_id'] = $goods_id;
                $where['status'] = 1;
                $market_goods = $d_market_goods->where($where)->find();
                if ($market_goods['mer_id'] == $mer_id) {
                    $this->error(L_('你不能虚拟上架自己的批发市场商品！'));
                }
                $market_express_template = M('Market_express_template');

                if ($market_goods['express_template_id'] > 0) {
                    $express_is_type = 0;
                    $market_goods['express_is_type'] = $market_express_template->where(array('id' => $market_goods['express_template_id']))->getField('is_type');
                    if ($market_goods['express_is_type']!=null) {
                        $express_is_type += 1;  // 处理后的值 1 快递和物流 2 快递 3 物流
                        if ($express_is_type!=1 && $express_is_type!=2) {
                            $this->error(L_('当前商品不能进行虚拟上架！'));
                        }
                    }

                }


                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $market_goods['image']);
                $pc_pic_list = array();
                $wap_pic_list = array();
                foreach ($tmp_pic_arr as $key => $value) {
                    $pc_pic_list[$key]['title'] = $wap_pic_list[$key]['title'] = $value;
                    $tmp_pic = $goods_image_class->get_image_by_path($value);
                    $pc_pic_list[$key]['url'] = $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                }
                $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);

                $market_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $market_goods['list'] = isset($return['list']) ? $return['list'] : '';


                $g_where['virtual_wholesale_goods_id'] = $market_goods['goods_id'];
                $g_where['virtual_wholesale_store_id'] = $market_goods['store_id'];
                $g_where['virtual_wholesale_mer_id'] = $mer_id;
                $g_where['goods_source_type'] = 3;
                $mall_goods = $model->goods_detail($g_where);
                $detail = $mall_goods['detail'];
                if ($detail && $detail['spec_val']) {
                    $list_old = $detail['spec_val']['list'];
                } else {
                    $list_old = array ();
                }

                foreach ($market_goods['list'] as $key=>&$vals) {
                    $vals['payment_pure_integral'] = $list_old[$key]['payment_pure_integral'];
                    $vals['exchange_limit_num'] = $list_old[$key]['exchange_limit_num'];
                }

                $info = array(
                    'market_goods_id' => $market_goods['goods_id'],
                    'goods_source_type' => 3,
                    'virtual_wholesale_mer_id' => $market_goods['mer_id'],
                    'virtual_wholesale_store_id' => $market_goods['store_id'],
                    'virtual_wholesale_goods_id' => $market_goods['goods_id'],
                    'goods_name' => $market_goods['name'],
                    'sku' => $market_goods['stock_num'],
                    'wap_pic_list' => $wap_pic_list,
                    'pc_pic_list' => $pc_pic_list,
                    'spec_list' => $market_goods['spec_list'],
                    'list' => $market_goods['list'],
                    'is_spec' =>  $market_goods['spec_list'] ? true : false,
                    'status' =>  1,
                    'express_template_id' =>  $market_goods['express_template_id'],
                    'express_fee' =>  $market_goods['express_fee'],
                    'freight_type' =>  $market_goods['freight_type'],
                    'goods_content' => replace_file_domain_content($market_goods['des']),
                );

                if ($detail && $detail['cat_fid'] > 0) {
                    $info['cat_fid'] = $detail['cat_fid'];
                }
                if ($detail && $detail['cat_id'] > 0) {
                    $info['cat_id'] = $detail['cat_id'];
                }
                if ($detail && $detail['deliver_type'] > 0) {
                    $info['deliver_type'] = $detail['deliver_type'];
                }
                if ($detail && $detail['exchange_limit_num'] > 0) {
                    $info['exchange_limit_num'] = $detail['exchange_limit_num'];
                }
                if ($detail && $detail['pc_pic_list'] > 0) {
                    $info['pc_pic_list'] = $detail['pc_pic_list'];
                }
                if ($detail && $detail['wap_pic_list'] > 0) {
                    $info['wap_pic_list'] = $detail['wap_pic_list'];
                }
                if ($detail && $detail['intro']) {
                    $info['intro'] = $detail['intro'];
                }
                if ($detail && $detail['invoice_content']) {
                    $info['invoice_content'] = $detail['invoice_content'];
                }
                if ($detail && $detail['goods_content']) {
                    $info['goods_content'] = $detail['goods_content'];
                }
                if ($detail && $detail['sort']) {
                    $info['sort'] = $detail['sort'];
                }
                if ($detail && $detail['is_new']) {
                    $info['is_new'] = $detail['is_new'];
                }
                if ($detail && $detail['status']) {
                    $info['status'] = $detail['status'];
                }
                if ($market_goods && $market_goods['goods_weight']>0) {
                    $info['goods_weight'] = $market_goods['goods_weight'];
                } elseif ($detail && $detail['goods_weight']>0) {
                    $info['goods_weight'] = $detail['goods_weight'];
                }
                if ($market_goods && $market_goods['goods_volume']>0) {
                    $info['goods_volume'] = $market_goods['goods_volume'];
                } elseif ($detail && $detail['goods_volume']>0) {
                    $info['goods_volume'] = $detail['goods_volume'];
                }

                $this->assign('info',$info);
            }

            // 运费模板信息
            if (1==$this->config['merchant_integral_mall_control']) {
                if ($market_goods['mer_id']>0) {
                    $where_express['mer_id'] = $market_goods['mer_id'];
                }
                $express_list = D('Market_express_template')->field(true)->where($where_express)->select();
                if (empty($express_list)) {
                    $this->error(L_('请先添加运费模板！'));
                }
                $this->assign('express_list', $express_list);
                if ($market_goods['express_template_id'] > 0) {
                    $tid = $market_goods['express_template_id'];
                    $template = D('Market_express_template')->field(true)->where(array('id' => $tid))->find();
                    if (empty($template)) {
                        $this->error(L_('不合法的模板信息！'));
                    }

                    $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $tid))->select();


                    $tmids = array();
                    if ($template_msgs) {
                        foreach ($template_msgs as $val) {
                            $tmids[] = $val['id'];
                            if (empty($template['msg'])) {
                                $template['msg'] = array();
                            }
                            $template['msg'][$val['id']] = $val;
                        }
                    }

                    if ($tmids) {
                        $areas = D('Market_express_template_area')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
                    } else {
                        $areas = D('Market_express_template_area')->field(true)->where(array('tid' => $tid))->select();
                    }

                    $area_list = array();
                    $area_info = array();
                    foreach ($areas as $arow) {
                        $area_list[$arow['vid']][] = $arow;
                        if (!$area_info[$arow['vid']]['area_info'] && $arow['area_name']) {
                            $area_info[$arow['vid']]['area_info'] .= "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
                        } elseif ($arow['area_name']) {
                            $area_info[$arow['vid']]['area_info'] .= ',' . "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
                        }
                    }

                    $values = D('Market_express_template_value')->field(true)->where(array('tid' => $tid))->select();
                    $template['value_list'] = array();
                    foreach ($values as $v) {
                        $v['area_list'] = $area_list[$v['id']];
                        $v['area_info'] = $area_info[$v['id']]['area_info'];
                        $msg = $template['msg'][$v['tmid']];
                        if($msg['freight_type']==1){
                            $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                            $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                            $v['add_weight'] = floatval($v['add_weight']);
                        }
                        $template['freight_type_'.$msg['type']] = $msg['freight_type'];
                        $template['value_list'][$msg['type']][] = $v;
                    }
                    $this->assign('template', $template);
                } else {
                    $template = D('Market_express_template')->field(true)->where($where_express)->order('id DESC')->find();
                    if (empty($template)) {
                        $this->error(L_('不合法的模板信息！'));
                    }
                    $this->assign('template', $template);
                }
            }

            $this->display();
        }
    }

    public function template_info() {
        $tid = I('tid',0,'intval');
        $template = D('Market_express_template')->field(true)->where(array('id' => $tid))->find();
        if (empty($template)) {
            $this->error(L_('不合法的模板信息！'));
        }

        $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $tid))->select();


        $tmids = array();
        if ($template_msgs) {
            foreach ($template_msgs as $val) {
                $tmids[] = $val['id'];
                if (empty($template['msg'])) {
                    $template['msg'] = array();
                }
                $template['msg'][$val['id']] = $val;
            }
        }

        if ($tmids) {
            $areas = D('Market_express_template_area')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
        } else {
            $areas = D('Market_express_template_area')->field(true)->where(array('tid' => $tid))->select();
        }

        $area_list = array();
        $area_info = array();
        foreach ($areas as $arow) {
            $area_list[$arow['vid']][] = $arow;
            if (!$area_info[$arow['vid']]['area_info'] && $arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
            } elseif ($arow['area_name']) {
                $area_info[$arow['vid']]['area_info'] .= ',' . "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
            }
        }

        $values = D('Market_express_template_value')->field(true)->where(array('tid' => $tid))->select();
        $template['value_list'] = array();
        foreach ($values as $v) {
            $v['area_list'] = $area_list[$v['id']];
            $v['area_info'] = $area_info[$v['id']]['area_info'];
            $msg = $template['msg'][$v['tmid']];
            if($msg['freight_type']==1){
                $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                $v['add_weight'] = floatval($v['add_weight']);
            }
            $template['freight_type_'.$msg['type']] = $msg['freight_type'];
            $template['value_list'][$msg['type']][] = $v;
        }
        exit(json_encode(array('status'=>1,'template'=>$template)));
    }


    //删除商品
    public function goods_del(){
        if(IS_POST){
            $goods_id=I('get.goods_id',0,'intval');
            if(empty($goods_id)){
                $this->error(L_('缺少参数！'));
            }
            $where=array();
            $where['goods_id']=$goods_id;
            $res=D('Merchant_integral_mall_goods')->goods_del($where);
            if(!$res){
                $this->error(L_('数据处理有误！'));
            }else{
                if($res['status']){
                    $this->success($res['msg']);
                }else{
                    $this->error($res['msg']);
                }
            }
        }else{
            $this->error(L_('访问页面有误！'));
        }
    }

    public function ajax_category(){
        if(IS_POST){
            $cat_fid = $_POST['cat_fid'] + 0;
            $cat_list = $this->_get_integral_mall_category($cat_fid);
            if($cat_list){
                exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
            }else{
                exit(json_encode(array('status'=>0,'cat_list'=>$cat_list)));
            }
        }else{
            $this->error(L_('访问页面有误！~~~'));
        }
    }

    private function _get_integral_mall_category($fid){
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
        $where['cat_fid'] = $fid;
        $mer_id = $_SESSION['merchant']['mer_id'];
        $where['mer_id'] = $mer_id;
        $where['cat_status'] = array('neq', 3);

        $category_list = $database_merchant_integral_mall_category->where($where)->getField('cat_id,cat_name');
        return $category_list;
    }

    public function category_list(){
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
        $cat_fid = $_GET['cat_fid'] + 0;

        $where['cat_status'] = array('neq', 3);
        $where['mer_id'] = $this->merchant_session['mer_id'];
        if(!$cat_fid){
            $where['cat_fid'] = 0;
        }else{
            $where['cat_fid'] = $Map['cat_id'] = $cat_fid;

            $now_category = $database_merchant_integral_mall_category->merchant_integral_mall_category_detail($Map);
            if(empty($now_category['detail'])){
                $this->error_tips('没有找到该分类信息！', 3, U('category_list'));
            }
            $this->assign('now_category', $now_category['detail']);
        }

        $category_list = $database_merchant_integral_mall_category->merchant_integral_mall_category_page_list($where, true, 'cat_sort DESC, cat_id DESC');
        $this->assign('category_list',$category_list['list']);
        $this->display();
    }

    public function category_add(){
        if(IS_POST){
            $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');

            if($_POST['cat_pic']){
                $_POST['cat_pic'] = str_replace(file_domain(),'',$_POST['cat_pic']);
            }

            if (!$_POST['cat_name']) {
                $this->error(L_('最少添加分类名称！'));
            }

            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            $result = $database_merchant_integral_mall_category->merchant_integral_mall_category_add($_POST);

            if(!$result){
                $this->error(L_('数据处理有误！'));
            }else{
                if($result['status']){
                    if ($_POST['cat_fid']>0) {
                        $this->success($result['msg'],U('Integral_mall/category_list',array('cat_fid'=>$_POST['cat_fid'])));
                    } else {
                        $this->success($result['msg'],U('Integral_mall/category_list'));
                    }
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $this->assign('bg_color','#F3F3F3');
            $this->display();
        }
    }

    public function category_edit(){
        $cat_id = $_GET['cat_id'] + 0;
        if(!$cat_id){
            $this->error(L_('传递参数有误！'));
        }
        $where['cat_id'] = $cat_id;
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');

        if(IS_POST){

            if($_POST['cat_pic']){
                $_POST['cat_pic'] = str_replace(file_domain(),'',$_POST['cat_pic']);
            }

            if (!$_POST['cat_name']) {
                $this->error(L_('最少添加分类名称！'));
            }

            $result = $database_merchant_integral_mall_category->merchant_integral_mall_category_edit($where,$_POST);
            if(!$result){
                $this->error(L_('数据处理有误！'));
            }else{
                if($result['status']){
                    if ($_POST['cat_fid']>0) {
                        $this->success($result['msg'],U('Integral_mall/category_list',array('cat_fid'=>$_POST['cat_fid'])));
                    } else {
                        $this->success($result['msg'],U('Integral_mall/category_list'));
                    }
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $now_category = $database_merchant_integral_mall_category->merchant_integral_mall_category_detail($where);
            if(!$now_category){
                $this->error(L_('数据处理有误！'));
            }
            $this->assign('now_category',$now_category['detail']);
            $this->display();
        }

    }

    public function category_del(){
        if(IS_POST){
            $cat_id = $_GET['cat_id'];
            if(!$cat_id){
                $this->error(0, '传递参数有误！');
            }

            $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
            $where['cat_id'] = $cat_id;
            $where['mer_id'] = $this->merchant_session['mer_id'];
            $result = $database_merchant_integral_mall_category->merchant_integral_mall_category_del($where);
            if(!$result){
                $this->error(0, '数据处理有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg']);
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }

    }

    // 商家积分商城首页配置
    public function config() {
        $this->display();
    }
    // 导航栏信息
    public  function index_slider(){
        $count  = M('Merchant_integral_mall_slider')->count();

        import('@.ORG.merchant_page');
        $p = new Page($count,15);
        $slider_list = M('Merchant_integral_mall_slider')->where(array('mer_id'=>$this->merchant_session['mer_id']))->order('sort DESC, id DESC')->limit($p->firstRow,$p->listRows)->select();

        $this->assign('slider_list',$slider_list);
        $this->display();
    }

    public  function slider_add(){
        if(IS_POST){

            if($_FILES['pic']['error'] != 4) {
                $image = D('Image')->handle($this->merchant_session['mer_id'], 'slider');
                if (!$image['error']) {

                    $_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
                } else {
                    $this->error( $image['msg']);
                }
            }

            if(empty($_POST['name']) || empty($_POST['url']) ){
                $this->error(L_('数据未填写完整'));
            }
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            $_POST['type_key'] = ($_POST['type_key']&&$_POST['type_key']!='')?$_POST['type_key']:'index_slider';

            if($res = M('Merchant_integral_mall_slider')->where(array('id'=>$_POST['id']))->find()){
                $id = $res['id'];
                $result = M('Merchant_integral_mall_slider')->where(array('id'=>$_POST['id']))->save($_POST);
            }else{
                if( empty($_POST['pic'])){
                    $this->error(L_('数据未填写完整'));
                }
                $result = M('Merchant_integral_mall_slider')->add($_POST);
                $id = $result;
            }
            if($result){
                D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
                S('index_list_'.$_POST['mer_id'],NULL);
                $this->success(L_('保存成功'));
            }else{
                $this->error(L_('保存失败'));
            }
        }else{
            $silder = M('Merchant_integral_mall_slider')->where(array('id'=>$_GET['id']))->find();
            $this->assign('slider',$silder);
            $this->display();
        }
    }

    public function slider_del(){
        $id = $_GET['id'];
        $where = array();
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $where['id'] = $id;
        M('Merchant_integral_mall_slider')->where($where)->delete();
        $this->success(L_('删除成功！'));
    }

    // 广告信息
    public  function index_advert(){
        $count  = M('Merchant_integral_mall_advert')->count();

        import('@.ORG.merchant_page');
        $p = new Page($count,15);
        $where = array('mer_id'=>$this->merchant_session['mer_id']);
        if ($_GET['type_key']) {
            $where['type_key'] = $_GET['type_key'];
        }

        $advert_list = M('Merchant_integral_mall_advert')->where($where)->order('sort DESC, id DESC')->limit($p->firstRow,$p->listRows)->select();

        $this->assign('advert_list',$advert_list);
        $this->display();
    }

    public  function advert_add(){
        if(IS_POST){

            if($_FILES['pic']['error'] != 4) {
                $image = D('Image')->handle($this->merchant_session['mer_id'], 'advert');
                if (!$image['error']) {

                    $_POST = array_merge($_POST, str_replace('/upload/advert/', '', $image['url']));
                } else {
                    $this->error( $image['msg']);
                }
            }

            if(empty($_POST['name']) || empty($_POST['url']) ){
                $this->error(L_('数据未填写完整'));
            }
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            $_POST['type_key'] = ($_POST['type_key']&&$_POST['type_key']!='')?$_POST['type_key']:'index_top_advert';

            if($res = M('Merchant_integral_mall_advert')->where(array('id'=>$_POST['id']))->find()){
                $id = $res['id'];
                $result = M('Merchant_integral_mall_advert')->where(array('id'=>$_POST['id']))->save($_POST);
            }else{
                if( empty($_POST['pic'])){
                    $this->error(L_('数据未填写完整'));
                }
                $result = M('Merchant_integral_mall_advert')->add($_POST);
                $id = $result;
            }
            if($result){
                D('Image')->update_table_id('/upload/advert/' . $_POST['pic'], $id, 'advert');
                S('index_adver_list_'.$_POST['mer_id'],NULL);
                $this->success(L_('保存成功'));
            }else{
                $this->error(L_('保存失败'));
            }
        }else{
            $advert = M('Merchant_integral_mall_advert')->where(array('id'=>$_GET['id']))->find();
            $this->assign('advert',$advert);
            $this->display();
        }
    }

    public function advert_del(){
        $id = $_GET['id'];
        $where = array();
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $where['id'] = $id;
        M('Merchant_integral_mall_advert')->where($where)->delete();
        $this->success(L_('删除成功！'));
    }



    // 销售订单-主要是发货和自提确认
    public function sell_order() {
        $data = array('mer_id' => $this->merchant_session['mer_id']);
        if (isset($_GET['goods_id']) && $_GET['goods_id']) {
            $data['goods_id'] = intval($_GET['goods_id']);
        }
        $data['virtual_wholesale_mer_id'] = $this->merchant_session['mer_id'];


        $result = D('Merchant_integral_mall_goods_order')->getList($data, true, true);
        $this->assign($result);
        $this->display();
    }

    // 自提
    public function self_mention() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $mer_id = $this->merchant_session['mer_id'];
        $where = array('order_id' => $order_id, 'mer_id' => $mer_id);
        $order = D('Merchant_integral_mall_goods_order')->field(true)->where($where)->find();
        if (empty($order)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单信息不正确')));
        }
        if ($order['status'] >= 5) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单已确认自提，请不要重复确认')));
        }
        if ($order['status'] != 1) {
            exit(json_encode(array('errcode' => 1, 'msg' => '此单当前不能确认自提')));
        }
        $data = array('status' => 5);
        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Merchant_integral_mall_goods_order')->where($where)->save($data)) {

            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 5, 'from_uid' => $mer_id, 'from_type' => 2,  'note' => '用户确认收货'));

            // 用户确认收货-计算给予商家结算
            D('Merchant_integral_mall_goods_order')->order_settlement($order);

            exit(json_encode(array('errcode' => 0, 'msg' => 'ok', 'url' => U('Integral_mall/sell_order'))));
        } else {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单状态修改失败，稍后重试')));
        }
    }


    // 积分商城订单详情
    public function order_detail()
    {
        $order_id = intval($_GET['order_id']);
        $mer_id = $this->merchant_session['mer_id'];
        $where = array('order_id' => $order_id);
        $order_info = D('Merchant_integral_mall_goods_order')->get_order_detail($where);
        if (!$order_info) {
            $this->error(L_('改订单信息不存在'));
        } elseif ($order_info['mer_id'] != $mer_id && $order_info['virtual_wholesale_mer_id'] != $mer_id) {
            $this->error(L_('当前订单不是此商户的订单'));
        } elseif ($order_info['spec_value'] && $order_info['spec_value']['list'] && $order_info['spec_index']) {
            $choose_spe = $order_info['spec_value']['list'][$order_info['spec_index']];
            $choose_spe['num'] = $order_info['num'];
            $this->assign('choose_spe', $choose_spe);
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //发货
    public function push()
    {
        $order_id = intval($_GET['order_id']);
        $mer_id = $this->merchant_session['mer_id'];
        $where = array('order_id' => $order_id);
        $order_info = D('Merchant_integral_mall_goods_order')->get_order_detail($where);
        if (!$order_info) {
            $this->error(L_('改订单信息不存在'));
        } elseif ($order_info['mer_id'] != $mer_id && $order_info['virtual_wholesale_mer_id'] != $mer_id) {
            $this->error(L_('当前订单不是此商户的订单'));
        }elseif ($order_info['status'] > 2) {
            $this->error(L_('当前订单已经收货'));
        }elseif ($order_info['status'] == 5) {
            $this->error(L_('当前订单已经自提'));
        }elseif ($order_info['status'] != 1 && $order_info['status'] != 2) {
            $this->error(L_('当前订单不能发货/自提'));
        } elseif ($order_info['spec_value'] && $order_info['spec_value']['list'] && $order_info['spec_index']) {
            $choose_spe = $order_info['spec_value']['list'][$order_info['spec_index']];
            $choose_spe['num'] = $order_info['num'];
            $this->assign('choose_spe', $choose_spe);
        }
        $this->assign('order_info', $order_info);

        if ($order_info) {
            if (IS_POST) {
                $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
                $express_number = isset($_POST['express_number']) ? htmlspecialchars(trim($_POST['express_number'])) : 0;
                $send_note = isset($_POST['send_note']) ? htmlspecialchars(trim($_POST['send_note'])) : '';
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
                $data['send_note'] = $send_note;
                $data['send_time'] = time();
                $data['status'] = 2;
                if (M('Merchant_integral_mall_goods_order')->where(array('order_id' => $order_id, 'mer_id' => $order_info['mer_id']))->save($data)) {
                    $sms_data = array('mer_id' => $mer_id, 'store_id' => 0, 'type' => 'integral_mall');
                    $now_user = M('User')->where(array('uid' => $order_info['uid']))->field('phone')->find();

                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $now_user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = L_('您在X1兑换了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$order_info['goods_name'],'X3'=>$order_info['num'] . '份','X4'=>$order_info['orderid']));
                    Sms::sendSms($sms_data);
                    D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'from_uid' => $mer_id, 'from_type' => 2, 'note' => '商户发货'));

                    $this->success(L_('发货成功'), U('Integral_mall/sell_order'));
                } else {
                    $this->error(L_('发货失败'));
                }
            } else {
                $this->assign('express_list', D('Express')->get_express_list());
                $this->display();
            }
        } else {
            $this->error(L_('错误的订单信息'));
        }
    }

}
