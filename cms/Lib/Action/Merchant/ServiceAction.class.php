<?php
class ServiceAction extends BaseAction
{
	 public function offer_info(){
        $publish_id = isset($_GET['publish_id']) ? intval($_GET['publish_id']) : 0;
        $offer_id = isset($_GET['offer_id']) ? intval($_GET['offer_id']) : 0;
        if ($publish_id) {
            $publishInfo = D('Service_user_publish')->where(array('publish_id'=> $publish_id))->find();
        } else {
            $offerInfo = D('Service_offer')->field(true)->where(array('offer_id' => $offer_id))->find();


            $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->find();
        }


        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name,accept_time')->find();
        if($catInfo){
            $publishInfo['cat_name'] = $catInfo['cat_name'];
        }



        if($publishInfo['catgory_type'] == 1){
            $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
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

            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->select();
            foreach ($offer_list as $key => $value) {
                $offer_list[$key]['msg_list'] = D('Service_offer_message')->where(array('offer_id'=>$value['offer_id']))->field('message,type,add_time')->order('add_time asc')->select();
                $offer_list[$key]['msg_count'] = count($offer_list[$key]['msg_list']);

            }
            $this->assign('offer_list',$offer_list);
            $this->assign('offer_count',count($offer_list));
        }else if($publishInfo['catgory_type'] == 2){

            $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $Deliver_supply = D('Deliver_supply')->where('order_id='.$publish_id.' AND item=3')->field('deliver_user_fee')->find();
            $cat_field_info['deliver_user_fee'] = $Deliver_supply['deliver_user_fee'];
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            foreach ($cat_field_info['img'] as &$item) {
                $item = replace_file_domain($item);
            }

            // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
            if ($cat_field_info['adress_id'] && !$cat_field_info['end_adress_name']) {
                $adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['adress_id']);

                $cat_field_info['end_adress_name'] = $adress_info['adress'].$adress_info['detail'];
            }
            // $cat_field_info['arrival_time']= $publishInfo['add_time']+($cat_field_info['arrival_time']*60); //发布时间加上分钟
            // $cat_field_info['arrival_time']=$cat_field_info['arrival_time']/60; //小时送达

            $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

            $plat_order_info = M('Plat_order')->where(array('business_type' => 'service', 'business_id' => $publishInfo['publish_id']))->field('pay_time, paid, is_mobile_pay, pay_type,refund_detail,is_refund,system_balance,pay_money')->find();
            if ($plat_order_info) {
                $paytypestr = D('Pay')->get_pay_name($plat_order_info['pay_type'], $plat_order_info['is_mobile_pay'], $plat_order_info['paid']);
                $cat_field_info['paytypestr'] = $paytypestr;
                $cat_field_info['paid'] = $plat_order_info['paid'] == 1 ? "已支付" : "未支付";
                $cat_field_info['pay_time'] = $plat_order_info['pay_time'];
            } else {
                $cat_field_info['paytypestr'] = '未支付';
                $cat_field_info['paid'] = '未支付';
                $cat_field_info['pay_time'] = '';
            }
            $cat_field_info['system_balance'] = $plat_order_info['system_balance'];
            $cat_field_info['pay_money'] = $plat_order_info['pay_money'];

            // 处理退款
            $cat_field_info['is_refund'] = $plat_order_info['is_refund'];
            $cat_field_info['refund_detail'] = unserialize($plat_order_info['refund_detail']);


            $this->assign('offer_info',$offer_info);

            $this->assign('cat_field_info',$cat_field_info);



        }else if($publishInfo['catgory_type'] == 3){
            $catInfo['accept_time']*60*60;

            $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $Deliver_supply = D('Deliver_supply')->where('order_id='.$publish_id.' AND item=3')->field('deliver_user_fee')->find();
            $cat_field_info['deliver_user_fee'] = $Deliver_supply['deliver_user_fee'];
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            foreach ($cat_field_info['img'] as &$item) {
                $item = replace_file_domain($item);
            }
            $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

            $plat_order_info = M('Plat_order')->where(array('business_type' => 'service', 'business_id' => $publishInfo['publish_id']))->field('pay_time, paid, is_mobile_pay, pay_type,refund_detail,is_refund,system_balance,pay_money')->find();
            if ($plat_order_info) {
                $paytypestr = D('Pay')->get_pay_name($plat_order_info['pay_type'], $plat_order_info['is_mobile_pay'], $plat_order_info['paid']);
                $cat_field_info['paytypestr'] = $paytypestr;
                $cat_field_info['paid'] = $plat_order_info['paid'] == 1 ? "已支付" : "未支付";
                $cat_field_info['pay_time'] = $plat_order_info['pay_time'];
            } else {
                $cat_field_info['paytypestr'] = '未支付';
                $cat_field_info['paid'] = '未支付';
                $cat_field_info['pay_time'] = '';
            }
            $cat_field_info['system_balance'] = $plat_order_info['system_balance'];
            $cat_field_info['pay_money'] = $plat_order_info['pay_money'];
            // 处理退款
            $cat_field_info['is_refund'] = $plat_order_info['is_refund'];
            $cat_field_info['refund_detail'] = unserialize($plat_order_info['refund_detail']);

            $this->assign('offer_info',$offer_info);

            // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
            if ($cat_field_info['start_adress_id'] && !$cat_field_info['start_adress_name']) {
                $start_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['start_adress_id']);
                $cat_field_info['start_adress_name'] = $start_adress_info['adress'].$start_adress_info['detail'];
            }
            if ($cat_field_info['end_adress_id'] && !$cat_field_info['end_adress_name']) {
                $end_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['end_adress_id']);
                $cat_field_info['end_adress_name'] = $end_adress_info['adress'].$end_adress_info['detail'];
            }

            fdump($cat_field_info, '$cat_field_info', true);
            $this->assign('cat_field_info',$cat_field_info);


        }

        // 查询发布服务快派用户注册的手机号
        if ($publishInfo['uid']) {
            $user_info = M('User')->field('phone')->where(array('uid' => $publishInfo['uid']))->find();
            if ($user_info['phone']) {
                $publishInfo['register_phone'] = $user_info['phone'];
            } else {
                $publishInfo['register_phone'] = '';
            }
        }
        // 查询流水号
        $orderid = M('Plat_order')->where(array('business_id' => $publishInfo['publish_id'], 'business_type' => 'service'))->getField('orderid');
        if ($orderid) {
            $publishInfo['plat_orderid'] = 'plat_' . $orderid;
        }
        $this->assign('publishInfo',$publishInfo);
        $this->display();
    }

    // 指定店铺订单列表
    public function auto_store_order_list(){
        $where = [];

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_sn') {
                $where['p.order_sn'] = ['like',"%".$_GET['keyword']."%"];
            }  else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = ['like',"%".$_GET['keyword']."%"];
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = ['like',"%".$_GET['keyword']."%"];
            }
        }

        if(!empty($_GET['start_time'])&&!empty($_GET['end_time'])){
            if ($_GET['start_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['start_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $where['so.add_time'] = ['between',[$period[0],$period[1]]];
        }

        $store_ids = array_column(M('Merchant_store')->where(['mer_id'=>$this->merchant_session['mer_id']])->select(), 'store_id');
        // 店铺搜索
        if(!empty($_GET['store_id'])){
        	if (in_array($_GET['store_id'], $store_ids)) {
            	$where['b.store_id'] = intval($_GET['store_id']);
        	}else{
        		$where['b.store_id'] = null;
        	}
        }else{
            $where['b.store_id'] = ['in',$store_ids]; // 指定店铺
        }

        // 已完成
        $where['p.status'] = 4;


        $return = D('Service_auto_category')->getOrderList($where,false);
        $autoStoreArray = D('Service_auto_category')->getAutoStoreAll($this->merchant_session['mer_id']);

        $this->assign($return);
        $this->assign('autoStoreArray',$autoStoreArray['store_list']);
        $this->display();
    }

    // 指定店铺订单列表导出
    public function auto_store_order_export(){
        $param = $_POST;
        $param['type'] = 'auto_store_order';
        $param['rand_number'] = time();
        $param['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }

}