<?php
class PayAction extends BaseAction{
    public function check(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
        if(!in_array($_GET['type'],array('merrecharge','auth','settlein'))){
            $this->error(L_('订单来源无法识别，请重试。'));
        }
        if($_GET['type'] == 'merrecharge'){
            $now_order = D('Merchant_recharge_order')->get_pay_order($this->merchant_session['mer_id'],intval($_GET['order_id']),true);
        }else if($_GET['type']=='auth'){
            $now_order['order_info'] = D('Merchant_auth')->get_pay_order($this->merchant_session['mer_id'],intval($_GET['order_id']));
        }else if($_GET['type']=='settlein'){
            $now_order = D('Merchant_settle_in_pay')->get_pay_order($this->merchant_session['mer_id'],intval($_GET['order_id']),true);
        } 
        if($now_order['error'] == 1){
            if($now_order['url']){
                $this->error_tips($now_order['msg'],$now_order['url']);
            }else{
                $this->error_tips($now_order['msg']);
            }
        }
        $now_merchant = D('Merchant')->get_info($this->merchant_session['mer_id']);
        if(empty($now_merchant)){
            $this->error(L_('未获取到您的帐号信息，请重试！'));
        }
        $order_info = $now_order['order_info'];
        $order_info['auth_id'] = $order_info['auth_id'] ? : 0;

        $pay_money = $order_info['order_total_money'];
        $pay_method = D('Config')->get_pay_method($notOnline,$notOffline);
        fdump($pay_method,'$pay_method',true);
//        $tmp_pay_method['weixin'] = $pay_method['weixin'];
//        $tmp_pay_method['allinpay'] = $pay_method['allinpay'];
        unset($pay_method['weixinapp']);
        unset($pay_method['yzfpay']);
        unset($pay_method['yzfpay_offline']);
        unset($pay_method['nmgpay']);


        if(empty($pay_method)){
            $this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
        }
        $this->assign('pay_method',$pay_method);

        $this->assign('order_info',$order_info);
        $this->assign('merchant',$now_merchant);

        $this->display();
    }
    protected function getPayName($label){
        $payName = array(
            'weixin' => L_('微信支付'),
            'tenpay' => L_('财付通支付'),
            'yeepay' => '银行卡支付(易宝支付)',
            'allinpay' => '银行卡支付(通联支付)',
            'chinabank' => '银行卡支付(网银在线)',
        );
        return $payName[$label];
    }
    public function go_pay(){
        if(empty($this->merchant_session)){
            $this->error_tips(L_('请先进行登录！'),U('Login/index'));
        }

        if(!in_array($_POST['order_type'],array('merrecharge','auth','settlein'))){
            $this->error_tips(L_('订单来源无法识别，请重试'));
        }

        if($_POST['order_type'] == 'merrecharge'){
            $now_order = D('Merchant_recharge_order')->get_pay_order($this->merchant_session['mer_id'],intval($_POST['order_id']),true);
        }else if($_POST['order_type']=='auth'){

            $now_order['order_info'] = D('Merchant_auth')->get_pay_order($this->merchant_session['mer_id'],intval($_POST['order_id']),intval($_POST['num']));
            if($now_order['order_info']['last_money']>0){
                $now_order['order_info']['money'] = $now_order['order_info']['last_money'];
            }
        }else if($_POST['order_type'] == 'settlein'){
            $now_order = D('Merchant_settle_in_pay')->get_pay_order($this->merchant_session['mer_id'],intval($_POST['order_id']),true);
        }


        if($now_order['error'] == 1){
            if($now_order['url']){
                $this->error_tips($now_order['msg'],$now_order['url']);
            }else{
                $this->error_tips($now_order['msg']);
            }
        }

        $order_info = $now_order['order_info'];
        //用户信息
        $now_merchant = D('Merchant')->get_info($this->merchant_session['mer_id']);
        if(empty($now_merchant)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }


        if($order_info['order_type'] == 'merrecharge'){
            $save_result = D('Merchant_recharge_order')->web_befor_pay($order_info,$now_merchant);
        }else if($_POST['order_type']=='auth'){
            $order_info['use_balance'] = $_POST['use_balance'];
            $save_result = D('Merchant_auth')->web_befor_pay($order_info,$now_merchant);
        }else if($_POST['order_type']=='settlein'){
            $save_result = D('Merchant_settle_in_pay')->web_befor_pay($order_info,$now_merchant);
        }
        fdump($save_result,'save');
        if($save_result['error_code']){
            $this->error($save_result['msg']);exit();
        }else if($save_result['url']){
            $this->assign('jumpUrl',$save_result['url']);
            $this->success($save_result['msg'],$save_result['url']);

            exit();
        }
        //需要支付的钱
        $pay_money = $save_result['pay_money'];
        $pay_method = D('Config')->get_pay_method($notOnline,$notOffline);


        if(empty($pay_method)){
            $this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
        }
        if(empty($pay_method[$_POST['pay_type']])){
            $this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
        }

        $pay_class_name = ucfirst($_POST['pay_type']);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
        }

        //用pay_id代替参数order_id传进在线支付
        $order_id = $order_info['order_id'];

        if($_POST['order_type']=='merrecharge'){
            $order_table = 'Merchant_recharge_order';
        }else if($_POST['order_type']=='auth'){
            $order_table = "Authority_group_order";
        }else if($_POST['order_type']=='settlein'){
            $order_table = "Merchant_settle_in_pay";
        }else{
            $order_table = ucfirst($_POST['order_type']).'_order';
        }

        $nowtime = date("ymdHis");
        $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant_session['mer_id']);
        $data_tmp['pay_type'] = $_POST['pay_type'];
        $data_tmp['order_type'] = $_POST['order_type'];
        $data_tmp['order_id'] = $order_id;
        $data_tmp['orderid'] = $orderid;
        $data_tmp['addtime'] = $nowtime;
        if(!D('Tmp_orderid')->add($data_tmp)){
            $this->error_tips(L_('更新失败，请联系管理员'));
        }
        $save_pay_id = D($order_table)->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
        if(!$save_pay_id){
            $this->error_tips(L_('更新失败，请联系管理员'));
        }else{
            $order_info['order_id']=$orderid;
        }
        if($pay_method[$_POST['pay_type']]['config']['rates'] > 0){
            $pay_money = getFormatNumber($pay_money*$pay_method[$_POST['pay_type']]['config']['rates']);
        }
        $pay_class = new $pay_class_name($order_info,$pay_money,$_POST['pay_type'],$pay_method[$_POST['pay_type']]['config'],$this->merchant_session,0);
        $go_pay_param = $pay_class->pay();

        fdump($pay_money,'ssss');

        if(empty($go_pay_param['error'])){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>1,
                    'info'=>$go_pay_param['qrcode'],
                    'orderid'=>$orderid,
                );
                header('Content-type: application/json');
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
        $this->display();
    }

    //异步通知
    public function notify_url(){
        $pay_method = D('Config')->get_pay_method();
        if(empty($pay_method)){
            $this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
        }
        if(empty($pay_method[$_GET['pay_type']])){
            $this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
        }

        $pay_class_name = ucfirst($_GET['pay_type']);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
        }

        $pay_class = new $pay_class_name('','',$_GET['pay_type'],$pay_method[$_GET['pay_type']]['config'],$this->merchant_session,0);
        $notify_return = $pay_class->notice_url();

        if(empty($notify_return['error'])){

        }else{
            $this->error_tips($notify_return['msg']);
        }
    }

    //跳转通知
    public function return_url(){
        $pay_type = isset($_GET['paytype']) ? $_GET['paytype'] : $_GET['pay_type'];

        $pay_method = D('Config')->get_pay_method();
        $pay_method_market = D('Market_order')->pay_method(true);

        if ($pay_method_market['weixinh5']) {
            $pay_method['weixinh5'] = $pay_method_market['weixinh5'];
        }
        if(empty($pay_method)){
            $this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
        }
        if(empty($pay_method[$pay_type])){
            $this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
        }

        $pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->merchant_session,0);
        $get_pay_param = $pay_class->return_url();

        if(empty($get_pay_param['error'])){
            if($get_pay_param['order_param']['order_type'] == 'merrecharge'){
                $now_order = $this->get_orderid('Merchant_recharge_order',$get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id']=$now_order['orderid'];
                $pay_info = D('Merchant_recharge_order')->after_pay($get_pay_param['order_param']);
            } elseif ($get_pay_param['order_param']['order_type'] == 'market') {
                $now_order = $this->get_orderid('Market_order',$get_pay_param['order_param']['order_id'],0);
                $pay_info = D('Market_order')->after_pay($get_pay_param['order_param'], true);
            }  elseif ($get_pay_param['order_param']['order_type'] == 'markets') {
                $now_order = $this->get_orderid('Market_total',$get_pay_param['order_param']['order_id'],0);
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            } elseif($get_pay_param['order_param']['order_type'] == 'auth'){
                $now_order = $this->get_orderid('Authority_group_order',$get_pay_param['order_param']['order_id'],0);
                $pay_info = D('Merchant_auth')->after_pay($get_pay_param['order_param']);
            
			} elseif ($get_pay_param['order_param']['order_type'] == 'newmarket') {
                $now_order = $this->get_orderid('new_marketing_order',$get_pay_param['order_param']['order_id'],0);
                $pay_info = D('New_marketing_order')->after_pay($get_pay_param['order_param']);
            
			} elseif($get_pay_param['order_param']['order_type'] == 'settlein'){
                $now_order = $this->get_orderid('Merchant_settle_in_pay',$get_pay_param['order_param']['order_id'],0);
                $pay_info = D('Merchant_settle_in_pay')->after_pay($get_pay_param['order_param']);
            }             else{
                $this->error(L_('订单类型非法！请重新下单。'));
            }


            $urltype = isset($_GET['urltype']) ? $_GET['urltype'] : '';
            if(empty($pay_info['error'])){
                if($get_pay_param['order_param']['pay_type'] == 'weixin'){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
                } elseif ($get_pay_param['order_param']['pay_type'] == 'baidu') {//百度的异步通知返回
                    exit("<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>");
                } elseif ('unionpay' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit(L_("验签成功"));
                }
                if(!empty($pay_info['url'])){
                    $this->assign('jumpUrl',$pay_info['url']);
                    $this->success(L_('订单付款成功！现在跳转.'));
                    exit();
                }
            }
            if(empty($pay_info['url'])){
                $this->error($pay_info['msg']);
            }else{
                if ($pay_info['error'] && $urltype == 'front') {
                    $this->redirect($pay_info['url']);
                    exit;
                }
                $this->error($pay_info['msg'],$pay_info['url']);exit;
            }
        }else{
            $this->error($get_pay_param['msg']);
        }
    }

    public function get_orderid($table,$orderid,$offline=0){
        $order =  D($table);
        $tmp_orderid = D('Tmp_orderid');
        if($offline){
            $now_order = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_order = $order->where(array('orderid'=>$orderid))->find();
            if(empty($now_order)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_order = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_order['orderid']=$orderid;
            }
        }
        if(empty($now_order)){
            $this->error(L_('该订单不存在'));
        }
        return $now_order;
    }


    //微信同步回调页面
    public function weixin_back(){ 
        switch($_GET['order_type']){
            case 'merrecharge':
                $now_order =$this->get_orderid('Merchant_recharge_order',$_GET['order_id']);
                break;
            case 'market':
                $now_order =$this->get_orderid('Market_order',$_GET['order_id']);
                break;
            case 'markets':
                $now_order =$this->get_orderid('Market_total',$_GET['order_id']);
                break;
            case 'auth':
                // $now_order =$this->get_orderid('Authority_group_order',$_GET['order_id']);
                redirect(C("config.site_url")."/merchant.php?g=Merchant&c=Merchant_money&a=auth_gorup_order_list");
                break;
            case 'settlein':
                $now_order =$this->get_orderid('Merchant_settle_in_pay',$_GET['order_id']);
                break;
            default:
                $this->error(L_('非法的订单'));
        }
        
        if(empty($now_order)){
            $this->error(L_('该订单不存在'));
        }
        $now_order['order_type'] = $_GET['order_type'];
        if($now_order['paid']){
            switch($_GET['order_type']){
                case 'merrecharge':
                    $now_pay_info_url = D('Merchant_recharge_order')->get_pay_after_url($now_order['label'],false,$now_order);
                    redirect($now_pay_info_url);
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Merchant_money&a=index';
                    break;
                case 'market':
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                    break;
                case 'markets':
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                    break;
                case 'settlein':
                    $redirctUrl = D('Merchant_settle_in_pay')->get_pay_after_url();
                    break;

            }
            redirect($redirctUrl);exit;
        }else if($_GET['pay_type'] == 'weifutong'){
            $this->error(L_('您还未支付'));
        }
        $tmpid = $now_order['order_id'];
        $now_order['order_id']  =   $now_order['orderid'];
        $import_result = import('@.ORG.pay.Weixin');
        $pay_method = D('Config')->get_pay_method();
        if(empty($pay_method)){
            $this->error(L_('系统管理员没开启任一一种支付方式！'));
        }
        $pay_class = new Weixin($now_order,0,'weixin',$pay_method['weixin']['config'],$this->merchant_session,1);
        $go_query_param = $pay_class->query_order();
        if($go_query_param['error'] === 0){
            switch($_GET['order_type']){
                case 'merrecharge':
                    $return = D('Merchant_recharge_order')->after_pay($go_query_param['order_param']);
                    if($return['url']){
                        redirect($return['url']);
                    }
                    break;
                case 'market':
                    $pay_info = D('Market_order')->after_pay($go_query_param['order_param'], true);
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                    if($redirctUrl){
                        redirect($redirctUrl);
                    }
                    break;
                case 'markets':
                    $pay_info = D('Market_order')->market_total_after_pay($go_query_param['order_param'], true);
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                    if($redirctUrl){
                        redirect($redirctUrl);
                    }
                    break;
                case 'settlein':
                    $return = D('Merchant_settle_in_pay')->after_pay($go_query_param['order_param']);
                    if($return['url']){
                        redirect($return['url']);
                    }
                    break;

            }
        }
        $now_order['order_id']  =   $tmpid;
        switch($_GET['order_type']){

            case 'merrecharge':
                $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Merchant_money&a=index';
                break;
            case 'market':
                $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                break;
            case 'markets':
                $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Market&a=buy_order';
                break;
            case 'settlein':
                $redirctUrl = D('Merchant_settle_in_pay')->get_pay_after_url();
                break;


        }
        redirect($redirctUrl);
    }
    //支付宝支付同步回调
    public function alipay_return(){
        $order_id_arr = explode('_',$_GET['out_trade_no']);
        $order_type = $order_id_arr[0];
        $order_id = $order_id_arr[1];
        switch($order_type){
            case 'merrecharge':
                $now_order = D('Merchant_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            case 'settlein':
                $now_order = D('Merchant_settle_in_pay')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                $this->error(L_('非法的订单'));
        }
        if($now_order['paid']){
            switch($order_type){

                case 'merrecharge':
                    $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Merchant_money&a=index';
                    break;
                case 'settlein':
                    $redirctUrl = D('Merchant_settle_in_pay')->get_pay_after_url();
                    break;

            }
            redirect($redirctUrl);exit;
        }
        $pay_method = D('Config')->get_pay_method();
        if(empty($pay_method)){
            $this->error(L_('系统管理员没开启任一一种支付方式！'));
        }
        $import_result = import('@.ORG.pay.Alipay');
        $pay_class = new Alipay('','',$order_type,$pay_method['alipay']['config'],$this->merchant_session,0);
        $go_query_param = $pay_class->query_order();
        if($go_query_param['error'] === 0){
            switch($order_type){
                case 'merrecharge':
                    D('Merchant_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'settlein':
                    D('Merchant_settle_in_pay')->after_pay($go_query_param['order_param']);
            }
        }
        switch($order_type){

            case 'merrecharge':
                $redirctUrl = C('config.site_url').'/merchant.php?g=Merchant&c=Merchant_money&a=recharge&order_id='.$now_order['order_id'];
                break;
            case 'settlein':
                $redirctUrl = D('Merchant_settle_in_pay')->get_pay_after_url();
        }
        redirect($redirctUrl);
    }

}
?>