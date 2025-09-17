<?php
// 商家余额
class Merchant_authModel extends Model{
    //增加商家权限
    public $time_type = array('year'=>'年','month'=>'月','day'=>'日');
    public $time_type_value = array('year'=>365,'month'=>30,'day'=>1);
    public function add_auth($mer_id,$auth_id,$type=0,$is_change=false){
        $now_merchant = D('Merchant')->get_info($mer_id);
        $now_merchant['menus'] = explode(',',$now_merchant['menus']);
        $menu_group  =  M('Authority_group')->where(array('id' => $auth_id))->find();
        $date = array('menus'=>$menu_group['menus'],'authority_group_id'=>$auth_id) ;
        fdump($is_change,'change');
        if($type==0){
            $date['is_try'] = 1;
            $date['try_end_time'] = $this->get_time_date($menu_group['try_time_type'])*$menu_group['try_time']*86400+time();
            $date['merchant_end_time'] = 0;
            $date['merchant_start_time']   =0;
        }else if($menu_group['is_default']==1){
			$date['is_try'] = 0;
		    $date['merchant_end_time'] = 0;
            $date['merchant_start_time']   =0;
            $date['try_end_time']   =0;
     
        }else{
            if($now_merchant['is_try'] && $auth_id = $now_merchant['authority_group_id']){

            }
            $date['is_try'] = 0;
            if($now_merchant['is_try'] && $auth_id = $now_merchant['authority_group_id']){
                $time_start = $now_merchant['try_end_time'];
            }else if($is_change){
                $now_merchant['merchant_start_time']=0;
                $time_start = $now_merchant['merchant_start_time']==0?time():$now_merchant['merchant_start_time'];
            }else{
                $time_start = $now_merchant['merchant_end_time']>0?$now_merchant['merchant_end_time']:time();
            }

            $date['merchant_start_time']   =$time_start;
            $date['merchant_end_time'] = $this->get_time_date($menu_group['time_type'])*$type*86400+$time_start;
            $date['try_end_time'] = 0;
        }

        fdump($date,'change',1);

        if(M('Merchant')->where(array('mer_id'=>$mer_id))->save($date)){
            M('Merchant_percent_rate')->where(array('mer_id'=>$mer_id))->setField('merchant_percent',$menu_group['percent']);
            return array('error_code'=>0,'msg'=>L_('权限更新成功'));
        }else{
            return array('error_code'=>1,'msg'=>L_('权限更新失败'));
        }
    }

    public function get_time_date($type){
        switch($type){
            case 'year':
                return 365;
            break;
            case 'month':
                return 30;
            break;
            case 'day':
                return 1;
            break;
        }
    }

    public function get_fid_menu($fid,$tmp=array()){
        $menu = M('New_merchant_menu')->where(array('id'=>$fid))->find();
        $tmp [] = $menu['id'];
        if($menu['fid']!=0){
            $tmp  = $this->get_fid_menu($menu['fid'],$tmp);
        }
        return $tmp;
    }

    public function get_pay_order($mer_id,$order_id,$num=1){
        $order  = M('Authority_group_order')->where(array('mer_id'=>$mer_id,'order_id'=>$order_id))->find();
        $now_merchant = D('Merchant')->get_info($mer_id);
        $res = $this->get_last_money($mer_id,$order['label'],$num);

        $auth = M('Authority_group')->where(array('id'=>$order['label']))->find();
        $date['money'] = $order['money'] = $res>=0?$res:$auth['price']*$num;
        $date['num']= $order['num'] = $num;


        M('Authority_group_order')->where(array('mer_id'=>$mer_id,'order_id'=>$order_id))->save($date);

        $order['order_name'] = $auth['name'];
        $order['auth_id'] = $auth['id'];
        $order['order_type'] = 'auth';
        $order['price'] = $auth['price'];
        $order['last_money'] = floatval($res);

        $now_auth =  M('Authority_group')->where(array('id' => $now_merchant['authority_group_id']))->find();

        $last_day = ($now_merchant['merchant_end_time']-time())/86400;
        $price_per_day = $now_auth['price']/$this->time_type_value[$now_auth['time_type']];

        $last_money = round($price_per_day*$last_day,2);
        $order['old_auth_money'] = floatval($last_money);
        $order['time_type_txt'] = $this->time_type[$auth['time_type']];


        fdump($order,'order');
        return $order;
    }

    public function web_befor_pay($order_info,$now_merchant){

        $pay_money = $order_info['money'];

        if($pay_money<=0){
            $order_result = $this->wap_pay_save_order($order_info);
            if ($order_result['error_code']) {
                return $order_result;
            }
            return $this->wap_after_pay_before($order_info);
        }

        if(!empty($now_merchant['money'])&&$order_info['use_balance']){
            if($now_merchant['money'] >= $pay_money){
                $data_auth_order['balance_pay'] = $pay_money;
                $this->wap_pay_save_order($order_info,$data_auth_order);
                return $this->wap_after_pay_before($order_info);
            }else{
                $data_auth_order['balance_pay'] = $now_merchant['money'];
                $pay_money -= $now_merchant['money'];
            }
        }

        $data_auth_order['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_auth_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
        $condition_auth_order['order_id'] = $order_info['order_id'];
        if(!M('Authority_group_order')->where($condition_auth_order)->data($data_auth_order)->save()){
            return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
        }
        return array('error_code'=>false,'pay_money'=>$pay_money);

    }

    public function wap_pay_save_order($order_info,$data_auth_order=array()){

        $data_auth_order['balance_pay']	 	= !empty($data_auth_order['balance_pay']) ? $data_auth_order['balance_pay'] : 0;
        $data_auth_order['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_auth_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
        $condition_group_order['order_id'] = $order_info['order_id'];
        if(M('Authority_group_order')->where($condition_group_order)->data($data_auth_order)->save()){
            return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
        }else{
            return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
        }
    }

    public function wap_after_pay_before($order_info){
        $order_param = array(
            'order_id' => $order_info['order_id'],
            'pay_type' => '',
            'third_id' => '',
            'is_mobile' => '0',
        );
        $result_after_pay = $this->after_pay($order_param);
        if($result_after_pay['error']){
            return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
        }
        return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>$this->get_pay_after_url());
    }


    public function after_pay($order_param){
        if($order_param['pay_type']!=''){
            $where['orderid'] = $order_param['order_id'];
        }else{
            $where['order_id'] = $order_param['order_id'];
        }
        $now_order = M('Authority_group_order')->field(true)->where($where)->find();

        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单不存在'));
        }else if($now_order['paid'] == 1){
            return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>$this->get_pay_after_url());
        }else{
            //得到当前商家信息，不将session作为调用值，因为可能会失效或错误。
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);

            if(empty($now_merchant)){
                return array('error'=>1,'msg'=>L_('没有查找到此订单归属的商家，请联系管理员！'));
            }

            $data_user_recharge_order = array();
            $data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
            $data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
            $data_user_recharge_order['payment_money'] = floatval($order_param['pay_money']);
            $data_user_recharge_order['pay_type'] = $order_param['pay_type'];
            $data_user_recharge_order['third_id'] = $order_param['third_id'];
            $data_user_recharge_order['paid'] = 1;
            if(M('Authority_group_order')->where($where)->save($data_user_recharge_order)){

                //$return  = D('Merchant_money_list')->add_money($now_order['mer_id'],'商家在线充值',$order_param);
                $order_param['desc']=L_('商家购买权限');
                $order_param['mer_id']=$now_order['mer_id'];

                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                $balance_pay = floatval($now_order['balance_pay']);
                if($balance_pay){
                    if($now_merchant['money'] < $balance_pay){
                        return array('error'=>1,'msg'=>L_('您的帐户余额不够此次支付'));
                    }
                }

                if(!empty($balance_pay)){
                    $use_result =  D('Merchant_money_list')->use_money($now_order['mer_id'],$balance_pay,'auth',L_('商家购买权限扣除余额'),$now_order['order_id']);
                    if($use_result['error_code']){
                        return array('error'=>1,'msg'=>$use_result['msg']);
                    }
                }


                $return = $this->add_auth($now_merchant['mer_id'],$now_order['label'],$now_order['num'],$now_order['is_change']);


                // 区域代理获得佣金
                D("Area_commission")->addCommission($now_order['money'], $now_order['mer_id'], 0, 'auth', $now_order['order_id']);

                //平台业务员得到分佣（扣除平台抽成后的金额，计算分佣）
                D('Bd')->addCommission($now_order['money'], $now_order['mer_id'], 0, 'auth', $now_order['order_id']);
                

                D('Scroll_msg')->add_msg('auth',$now_merchant['mer_id'],L_('商家【x1】于x2购买权限成功！',array('x1'=>str_replace_name($now_merchant['name']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

                return array('error'=>0,'msg'=>L_('购买权限成功！'),'url'=>$this->get_pay_after_url());

            }else{
                return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }


    public function get_pay_after_url(){

        return U('Merchant/Merchant_money/auth_gorup_order_list',['refresh'=>1]);


    }

    public function get_auth_group_list(){
        $menus_group = M('Authority_group')->where(array('gid' => 1,'status'=>1))->order('sort DESC,id desc')->select();
        foreach ($menus_group as &$item) {
            $item['detail'] = explode(PHP_EOL,$item['detail']);
            $item['time_type_txt'] = $this->time_type[$item['time_type']];
        }

        return $menus_group;
    }

    public function get_auth_group_list_wap($page = 20,$is_has_default = 1){

        if($is_has_default == 0) { //不包含默认权限
            $where['is_default'] = 0;
        }
        $where['gid'] = 1;
        $where['status'] = 1;
        $count = M('Authority_group')->where($where)->count();
        $p = new Page($count, $page);
        $menus_group = M('Authority_group')->where($where)->order('sort DESC,id desc')->limit($p->firstRow.','.$p->listRows)->select();

        foreach ($menus_group as &$item) {
            $item['detail'] = explode(PHP_EOL,$item['detail']);
            $item['time_type_txt'] = $this->time_type[$item['time_type']];
            $item['try_time_type_txt'] = $this->time_type[$item['try_time_type']];
        }

        $group['group_list'] = $menus_group ? $menus_group : [];
        $group['total_count'] = $count;
        $group['totalPage'] = ceil($count/$page);

        return $group;
    }

    //计算这个套餐剩余的钱
    public function get_last_money($mer_id,$auth_id,$num=1){
        $now_merchant = D('Merchant')->get_info($mer_id);
        $now_auth =  M('Authority_group')->where(array('id' => $now_merchant['authority_group_id']))->find();
        $buy_auth =  M('Authority_group')->where(array('id' => $auth_id))->find();

        if($auth_id==$now_auth['id']){
            return -1;
        }

        if($now_auth['is_default'] || ($now_auth['is_try'] && $now_merchant['is_try'])||empty($now_auth)){
            return -1;
        }

        $last_day = ($now_merchant['merchant_end_time']-time())/86400;
        $price_per_day = $now_auth['price']/$this->time_type_value[$now_auth['time_type']];

        $last_money = round($price_per_day*$last_day,2);

        if($buy_auth['price']*$num>$last_money){
            return $buy_auth['price']*$num-$last_money;
        }else{
            return 0;
        }
    }
}