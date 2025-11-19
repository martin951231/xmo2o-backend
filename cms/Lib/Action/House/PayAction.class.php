<?php
class PayAction extends BaseAction{
    public function check(){
        if(empty($this->house_session)){
            $this->error('请先进行登录！',U('Login/index'));
        }
        if(!in_array($_GET['type'],array('sqrecharge'))){
            $this->error('订单来源无法识别，请重试。');
        }
        if($_GET['type'] == 'sqrecharge'){
            $now_village = D('Village_recharge_order')->get_pay_order($this->house_session['village_id'],intval($_GET['order_id']),true);
        }
        if($now_village['error'] == 1){
            if($now_village['url']){
                $this->error_tips($now_village['msg'],$now_village['url']);
            }else{
                $this->error_tips($now_village['msg']);
            }
        }
        $now_store = D('House_village')->get_one($this->house_session['village_id']);
        if(empty($now_store)){
            $this->error_tips('未获取到您的帐号信息，请重试！');
        }
        $order_info = $now_village['order_info'];

        $pay_money = $order_info['order_total_money'];
        $pay_method = D('Config')->get_pay_method();
      
        if ($pay_method['allinpay']) {
            $tmp_pay_method['allinpay'] = $pay_method['allinpay'];
        }
        if ($pay_method['weixin']) {
            $tmp_pay_method['weixin'] = $pay_method['weixin'];
        }
        if ($pay_method['alipay']) {
            $tmp_pay_method['alipay'] = $pay_method['alipay'];
        }
        if(empty($pay_method)){
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        $this->assign('pay_method',$tmp_pay_method);

        $this->assign('order_info',$order_info);

        $this->display();
    }
    protected function getPayName($label){
        $payName = array(
            'weixin' => '微信支付',
            'tenpay' => '财付通支付',
            'yeepay' => '银行卡支付(易宝支付)',
            'allinpay' => '银行卡支付(通联支付)',
            'chinabank' => '银行卡支付(网银在线)',
        );
        return $payName[$label];
    }
    public function go_pay(){

        $pay_class_name = ucfirst($_POST['pay_type']);
        fdump_api(['msg' => '发起购买短信或小区充值余额支付', 'post' => $_POST], 'smsBuy/go_pay_log', 1);
        if(empty($this->house_session)){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'请先进行登录！',
                );
                fdump_api(['msg' => '请先进行登录，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '请先进行登录，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('请先进行登录！',U('Login/index'));
        }
        if(!in_array($_POST['order_type'],array('sqrecharge','sqsmsbuy'))){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'订单来源无法识别，请重试。',
                );
                fdump_api(['msg' => '订单来源无法识别，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '订单来源无法识别，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('订单来源无法识别，请重试。');
        }

        if($_POST['order_type'] == 'sqrecharge'){
            $now_village = D('Village_recharge_order')->get_pay_order($this->house_session['village_id'],intval($_POST['order_id']),true);
            fdump_api(['msg' => '小区充值余额', 'order_id' => intval($_POST['order_id']), 'village_id' => $this->house_session['village_id'], 'now_village' => $now_village], 'smsBuy/go_pay_log', 1);
        }elseif($_POST['order_type'] == 'sqsmsbuy'){
            $order_info = D('Sms_buy_order')->where(array('order_id'=>$_POST['order_id'],'type_id'=>$this->house_session['village_id'], 'type' => 1))->find();
            $order_info['order_type'] = 'sqsmsbuy';
            $order_info['order_name'] = '充值短信';
            fdump_api(['msg' => 'sqsmsbuy', 'order_id' => $_POST['order_id'], 'village_id' => $this->house_session['village_id'], 'order_info' => $order_info], 'smsBuy/go_pay_log', 1);
        }

        if($now_village['error'] == 1){
            if($now_village['url']){
                if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                    $returnArr = array(
                        'status'=>0,
                        'info'=>$now_village['msg'],
                    );
                    fdump_api(['msg' => '小区小区充值余额返回错误', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                    header('Content-type: application/json');
                    exit(json_encode($returnArr));
                }
                $this->error($now_village['msg'],$now_village['url']);
            }else{
                if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                    $returnArr = array(
                        'status'=>0,
                        'info'=>$now_village['msg'],
                    );
                    fdump_api(['msg' => '小区小区充值余额返回错误', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                    exit(json_encode($returnArr));
                }
                $this->error($now_village['msg']);
            }
        }

        if($_POST['order_type'] == 'sqrecharge'){
            $order_info = $now_village['order_info'];
            fdump_api(['msg' => '小区购买短信订单信息赋予', 'order_info' => $order_info], 'smsBuy/go_pay_log', 1);
        }
        
        //用户信息
        $now_store = D('House_village')->get_one($this->house_session['village_id']);
        if(empty($now_store)){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'未获取到您的帐号信息，请重试！',
                );
                fdump_api(['msg' => '未获取到您的帐号信息，请重试', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '未获取到您的帐号信息，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('未获取到您的帐号信息，请重试！');
        }

        if($order_info['order_type'] == 'sqrecharge'){
            $save_result = D('Village_recharge_order')->web_befor_pay($order_info,$now_store);
            fdump_api(['msg' => '小区短信订单数据更新', 'order_info' => $order_info, 'now_store' => $now_store, 'save_result' => $save_result], 'smsBuy/go_pay_log', 1);

            if($save_result['error_code']){
                fdump_api(['msg' => '返回报错', 'err' => $save_result['msg']], 'smsBuy/go_pay_log', 1);
                $this->error($save_result['msg']);exit();
            }else if($save_result['url']){
                $this->assign('jumpUrl',$save_result['url']);
                fdump_api(['msg' => '返回报错', 'err' => $save_result['msg'], 'jumpUrl' => $save_result['url']], 'smsBuy/go_pay_log', 1);
                $this->success($save_result['msg']);exit();
            }
        }

        
        //需要支付的钱
        if($order_info['order_type'] == 'sqrecharge'){
            $pay_money = $save_result['pay_money'];
        }elseif ($order_info['order_type'] == 'sqsmsbuy') {
            $pay_money = $order_info['payment_money'];
        }

        fdump_api(['msg' => '需要支付的钱', 'pay_money' => $pay_money], 'smsBuy/go_pay_log', 1);

        $pay_method = D('Config')->get_pay_method();


        fdump_api(['msg' => '支持的支付方式', 'pay_method' => $pay_method], 'smsBuy/go_pay_log', 1);
        if(empty($pay_method)){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'系统管理员没开启任一一种支付方式！',
                );
                fdump_api(['msg' => '系统管理员没开启任一一种支付方式', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '系统管理员没开启任一一种支付方式',  'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$_POST['pay_type']])){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'您选择的支付方式不存在，请更换支付方式！',
                );
                fdump_api(['msg' => '您选择的支付方式不存在，请更换支付方式', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                header('Content-type: application/json');
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '您选择的支付方式不存在，请更换支付方式', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('您选择的支付方式不存在，请更换支付方式！');
        }

        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'系统管理员暂未开启该支付方式，请更换其他的支付方式',
                );
                fdump_api(['msg' => '系统管理员暂未开启该支付方式，请更换其他的支付方式', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '系统管理员暂未开启该支付方式，请更换其他的支付方式', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }

        //用pay_id代替参数order_id传进在线支付
        $order_id = $order_info['order_id'];

        if($_POST['order_type']=='sqrecharge'){
            $order_table = 'Village_recharge_order';
        }else{
            $order_table = ucfirst($_POST['order_type']).'_order';
        }
        fdump_api(['msg' => '对应订单表', 'order_table' => $order_table], 'smsBuy/go_pay_log', 1);

        $nowtime = date("ymdHis");
        if($order_info['order_type'] == 'sqsmsbuy'){
            $orderid = $order_info['orderid'];
        }else{
            $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->house_session['village_id']);
        }
        fdump_api(['msg' => '订单', 'orderid' => $orderid], 'smsBuy/go_pay_log', 1);
        
        $data_tmp['pay_type'] = $_POST['pay_type'];
        $data_tmp['order_type'] = $_POST['order_type'];
        $data_tmp['order_id'] = $order_id;
        $data_tmp['orderid'] = $orderid;
        $data_tmp['addtime'] = $nowtime;
        fdump_api(['msg' => '记录临时订单', 'data_tmp' => $data_tmp], 'smsBuy/go_pay_log', 1);
        if(!D('Tmp_orderid')->add($data_tmp)){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>0,
                    'info'=>'更新失败，请联系管理员',
                );
                fdump_api(['msg' => '更新失败，请联系管理员', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '更新失败，请联系管理员', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
            $this->error('更新失败，请联系管理员');
        }

        if($order_info['order_type'] != 'sqsmsbuy'){
            $save_pay_id = D($order_table)->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
            fdump_api(['msg' => '更新查订单id', 'order_id' => $order_id,'orderid' => $orderid, 'save_pay_id' => $save_pay_id], 'smsBuy/go_pay_log', 1);
            if(!$save_pay_id){
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                    $returnArr = array(
                        'status'=>0,
                        'info'=>'更新失败，请联系管理员',
                    );
                    fdump_api(['msg' => '更新失败，请联系管理员', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                    exit(json_encode($returnArr));
                }
                fdump_api(['msg' => '更新失败，请联系管理员', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
                $this->error('更新失败，请联系管理员');
            }
        }

        $order_info['order_id']=$orderid;
        if($pay_class_name == 'Alipay'){
            //支付宝扫码支付
            $order_info['pay_func']='qrcodePay';
        }
        $pay_config = $pay_method[$_POST['pay_type']]['config'];
        fdump_api(['msg' => '支付配置', 'pay_money' => $pay_money, 'pay_type' => $_POST['pay_type'], 'pay_config' => $pay_config, 'house_session' => $this->house_session], 'smsBuy/go_pay_log', 1);
        $pay_class = new $pay_class_name($order_info,$pay_money,$_POST['pay_type'],$pay_config,$this->house_session,0);
        $go_pay_param = $pay_class->pay();
        fdump_api(['msg' => '发起支付结果', 'go_pay_param' => $go_pay_param], 'smsBuy/go_pay_log', 1);

        if(empty($go_pay_param['error'])){
            if(isset($go_pay_param['qr_code']) && $pay_class_name == 'Alipay'){
                $returnArr = array(
                    'status'=>1,
                    'info'=>$go_pay_param['qr_code'],
                    'orderid'=>$orderid,
                );
                fdump_api(['msg' => '返回结果', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                header('Content-type: application/json');
                echo json_encode($returnArr);
                exit;
            }elseif($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                $returnArr = array(
                    'status'=>1,
                    'info'=>$go_pay_param['qrcode'],
                    'orderid'=>$orderid,
                );
                fdump_api(['msg' => '返回结果', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                header('Content-type: application/json');
                echo json_encode($returnArr);
                exit;
            }else if(!empty($go_pay_param['url'])){
                fdump_api(['msg' => '渲染链接url', 'url' => $go_pay_param['url']], 'smsBuy/go_pay_log', 1);
                $this->assign('url',$go_pay_param['url']);
            }else if(!empty($go_pay_param['form'])){
                fdump_api(['msg' => '渲染链接form', 'form' => $go_pay_param['form']], 'smsBuy/go_pay_log', 1);
                $this->assign('form',$go_pay_param['form']);
            }else{
                if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong'){
                    $returnArr = array(
                        'status'=>0,
                        'info'=>'调用支付发生错误，请重试。',
                    );
                    fdump_api(['msg' => '调用支付发生错误，请重试', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                    header('Content-type: application/json');
                    exit(json_encode($returnArr));
                }
                fdump_api(['msg' => '调用支付发生错误，请重试', 'pay_class_name' => $pay_class_name], 'smsBuy/go_pay_log', 1);
                $this->error('调用支付发生错误，请重试。');
            }
        }else{
            if($pay_class_name == 'Weixin' || $pay_class_name == 'Weifutong' ||$pay_class_name == 'Alipay' ){
                $returnArr = array(
                    'status'=>0,
                    'info'=>$go_pay_param['msg'],
                );
                fdump_api(['msg' => '反馈错误信息', 'pay_class_name' => $pay_class_name, 'returnArr' => $returnArr], 'smsBuy/go_pay_log', 1);
                header('Content-type: application/json');
                exit(json_encode($returnArr));
            }
            fdump_api(['msg' => '反馈错误信息', 'pay_class_name' => $pay_class_name, 'err' => $go_pay_param['msg']], 'smsBuy/go_pay_log', 1);
            $this->error($go_pay_param['msg']);
        }
        $this->display();
    }

    //异步通知
    public function notify_url(){
        fdump_api(['msg' => '异步通知', 'post' => $_POST, 'get' => $_GET], 'smsBuy/notify_url_log', 1);
        $pay_method = D('Config')->get_pay_method();
        fdump_api(['msg' => '支付方式', 'pay_method' => $pay_method], 'smsBuy/notify_url_log', 1);
        if(empty($pay_method)){
            fdump_api(['msg' => '系统管理员没开启任一一种支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$_GET['pay_type']])){
            fdump_api(['msg' => '您选择的支付方式不存在，请更新支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }

        $pay_class_name = ucfirst($_GET['pay_type']);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            fdump_api(['msg' => '系统管理员暂未开启该支付方式，请更换其他的支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }
        $pay_config = $pay_method[$_GET['pay_type']]['config'];
        fdump_api(['msg' => '支付配置', 'pay_type' => $_GET['pay_type'], 'pay_config' => $pay_config, 'house_session' => $this->house_session], 'smsBuy/notify_url_log', 1);
        $pay_class = new $pay_class_name('','',$_GET['pay_type'],$pay_config,$this->house_session,0);
        $notify_return = $pay_class->notice_url();
        fdump_api(['msg' => '返回结果',  'notify_return' => $notify_return], 'smsBuy/notify_url_log', 1);

        if(empty($notify_return['error'])){

        }else{
            $this->error_tips($notify_return['msg']);
        }
    }

    //跳转通知
    public function return_url(){
        fdump_api(['msg' => '跳转通知', 'post' => $_POST, 'get' => $_GET], 'smsBuy/notify_url_log', 1);
        $pay_type = isset($_GET['paytype']) ? $_GET['paytype'] : $_GET['pay_type'];

        $pay_method = D('Config')->get_pay_method();
        fdump_api(['msg' => '支付方式', 'pay_method' => $pay_method], 'smsBuy/notify_url_log', 1);

        if(empty($pay_method)){
            fdump_api(['msg' => '系统管理员没开启任一一种支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('系统管理员没开启任一一种支付方式！');
        }
        if(empty($pay_method[$pay_type])){
            fdump_api(['msg' => '您选择的支付方式不存在，请更新支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('您选择的支付方式不存在，请更新支付方式！');
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            fdump_api(['msg' => '系统管理员暂未开启该支付方式，请更换其他的支付方式'], 'smsBuy/notify_url_log', 1);
            $this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
        }

        $pay_config = $pay_method[$pay_type]['config'];
        fdump_api(['msg' => '支付配置', 'pay_type' => $pay_type, 'pay_config' => $pay_config, 'house_session' => $this->house_session], 'smsBuy/notify_url_log', 1);
        $pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->house_session,0);
        $get_pay_param = $pay_class->return_url();
        fdump_api(['msg' => '返回结果',  'get_pay_param' => $get_pay_param], 'smsBuy/notify_url_log', 1);

        if(empty($get_pay_param['error'])){
            if($get_pay_param['order_param']['order_type'] == 'sqrecharge'){
                $now_village = $this->get_orderid('Village_recharge_order',$get_pay_param['order_param']['order_id'],0);
                fdump_api(['msg' => '充值获取下订单信息',  'order_id' => $get_pay_param['order_param']['order_id'],  'now_village' => $now_village], 'smsBuy/notify_url_log', 1);
                $get_pay_param['order_param']['order_id']=$now_village['orderid'];
                $pay_info = D('Village_recharge_order')->after_pay($get_pay_param['order_param']);
                fdump_api(['msg' => '充值回调支付后返回',  'pay_info' => $pay_info], 'smsBuy/notify_url_log', 1);
            }elseif ($get_pay_param['order_param']['order_type'] == 'sqsmsbuy') {
                $now_village = $this->get_orderid('Sms_buy_order',$get_pay_param['order_param']['order_id'],0);
                fdump_api(['msg' => '购买短信获取下订单信息',  'order_id' => $get_pay_param['order_param']['order_id'],  'now_village' => $now_village], 'smsBuy/notify_url_log', 1);
                $get_pay_param['order_param']['order_id']=$now_village['orderid'];
                $pay_info = D('Sms_buy_order')->after_pay($get_pay_param['order_param']);
                fdump_api(['msg' => '购买短信回调支付后返回',  'pay_info' => $pay_info], 'smsBuy/notify_url_log', 1);
            }else{
                $this->error('订单类型非法！请重新下单。');
            }


            $urltype = isset($_GET['urltype']) ? $_GET['urltype'] : '';
            if(empty($pay_info['error'])){
                if($get_pay_param['order_param']['pay_type'] == 'weixin'){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
                } elseif ($get_pay_param['order_param']['pay_type'] == 'baidu') {//百度的异步通知返回
                    exit("<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>");
                } elseif ('unionpay' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit("验签成功");
                }
                if(!empty($pay_info['url'])){
                    $this->assign('jumpUrl',$pay_info['url']);
                    $this->success('订单付款成功！现在跳转.');
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
            $now_village = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_village = $order->where(array('orderid'=>$orderid))->find();
            if(empty($now_village)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_village = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_village['orderid']=$orderid;
            }
        }
        if(empty($now_village)){
            $this->error('该订单不存在');
        }
        return $now_village;
    }


    //微信同步回调页面
    public function weixin_back(){
        fdump_api(['msg' => '微信同步回调页面',  'get' => $_GET,  'post' => $_POST], 'smsBuy/weixin_back_log', 1);
        switch($_GET['order_type']){
            case 'sqrecharge':
                $now_village =$this->get_orderid('Village_recharge_order',$_GET['order_id']);
                break;
            case 'sqsmsbuy':
                $now_village =$this->get_orderid('Sms_buy_order',$_GET['order_id']);
                break;
            default:
                $this->error('非法的订单');
        }
        fdump_api(['msg' => '订单信息',  'now_village' => $now_village], 'smsBuy/weixin_back_log', 1);

        if(empty($now_village)){
            fdump_api(['msg' => '该订单不存在'], 'smsBuy/weixin_back_log', 1);
            $this->error('该订单不存在');
        }
        $now_village['order_type'] = $_GET['order_type'];
        if($now_village['paid']){
            switch($_GET['order_type']){

                case 'sqrecharge':
                    $redirctUrl = C('config.site_url').'/shequ.php?g=House&c=Village_money&a=money_list';
                    break;
                case 'sqsms':
                    $redirctUrl = C('config.site_url').'/shequ.php?g=House&c=Village_money&a=sms_note&type=buy';
                    break;
            }
            fdump_api(['msg' => '返回条状页面链接【已支付】',  'redirctUrl' => $redirctUrl], 'smsBuy/weixin_back_log', 1);
            redirect($redirctUrl);exit;
        }else if($_GET['pay_type'] == 'weifutong'){
            fdump_api(['msg' => '您还未支付'], 'smsBuy/weixin_back_log', 1);
            $this->error('您还未支付');
        }
        $tmpid = $now_village['order_id'];
        $now_village['order_id']  =   $now_village['orderid'];
        $pay_method = D('Config')->get_pay_method();
        if($_GET['pay_type']=='alipay'){
            $import_result = import('@.ORG.pay.Alipay');
            fdump_api(['msg' => '支付方法',  'config' => $pay_method['alipay']['config'], 'house_session' => $this->house_session], 'smsBuy/weixin_back_log', 1);
            $pay_class = new Alipay($now_village,0,'alipay',$pay_method['alipay']['config'],$this->house_session,0);
            $go_query_param = $pay_class->queryTradeOrder();
        }else{
            $import_result = import('@.ORG.pay.Weixin');
            fdump_api(['msg' => '支付方法',  'pay_method' => $pay_method], 'smsBuy/weixin_back_log', 1);
            if(empty($pay_method)){
                $this->error('系统管理员没开启任一一种支付方式！');
            }
            fdump_api(['msg' => '支付方法',  'config' => $pay_method['weixin']['config'], 'house_session' => $this->house_session], 'smsBuy/weixin_back_log', 1);
            $pay_class = new Weixin($now_village,0,'weixin',$pay_method['weixin']['config'],$this->house_session,1);
            $go_query_param = $pay_class->query_order();
        }

        fdump_api(['msg' => '查找微信订单信息',  'go_query_param' => $go_query_param], 'smsBuy/weixin_back_log', 1);
        if($go_query_param['error'] === 0){
            switch($_GET['order_type']){
                case 'sqrecharge':
                    fdump_api(['msg' => '反馈支付回调',  'order_param' => $go_query_param['order_param']], 'smsBuy/weixin_back_log', 1);
                    D('Village_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'sqsmsbuy':
                    fdump_api(['msg' => '反馈支付回调',  'order_param' => $go_query_param['order_param']], 'smsBuy/weixin_back_log', 1);
                    D('Sms_buy_order')->after_pay($go_query_param['order_param']);
                    break;

            }
        }
        $now_village['order_id']  =   $tmpid;
        switch($_GET['order_type']){

            case 'sqrecharge':
                $redirctUrl = C('config.site_url').'/shequ.php?g=House&c=Village_money&a=money_list';
                break;
            case 'sqsmsbuy':
                $redirctUrl = C('config.site_url').'/shequ.php?g=House&c=Village_money&a=sms_note&type=buy';
                break;


        }
        fdump_api(['msg' => '返回条状页面链接',  'redirctUrl' => $redirctUrl], 'smsBuy/weixin_back_log', 1);
        redirect($redirctUrl);
    }
    //支付宝支付同步回调
    public function alipay_return(){
        fdump_api(['msg' => '支付宝支付同步回调',  'get' => $_GET,  'post' => $_POST], 'smsBuy/alipay_return_log', 1);
        $order_id_arr = explode('_',$_GET['out_trade_no']);
        $order_type = $order_id_arr[0];
        $order_id = $order_id_arr[1];
        switch($order_type){
            case 'sqrecharge':
                $now_village = D('Village_recharge_order')->where(array('orderid'=>$order_id))->find();
                break;
            default:
                fdump_api(['msg' => '非法的订单'], 'smsBuy/alipay_return_log', 1);
                $this->error('非法的订单');
        }
        fdump_api(['msg' => '订单信息',  'now_village' => $now_village], 'smsBuy/alipay_return_log', 1);
        if($now_village['paid']){
            switch($order_type){

                case 'sqrecharge':
                    $redirctUrl = C('config.site_url').'/shequ.php?g=Store&c=Store_money&a=index';
                    break;

            }
            fdump_api(['msg' => '返回条状页面链接【已支付】',  'redirctUrl' => $redirctUrl], 'smsBuy/alipay_return_log', 1);
            redirect($redirctUrl);exit;
        }
        $pay_method = D('Config')->get_pay_method();
        fdump_api(['msg' => '支付方法',  'pay_method' => $pay_method], 'smsBuy/alipay_return_log', 1);
        if(empty($pay_method)){
            fdump_api(['msg' => '系统管理员没开启任一一种支付方式'], 'smsBuy/alipay_return_log', 1);
            $this->error('系统管理员没开启任一一种支付方式！');
        }
        $import_result = import('@.ORG.pay.Alipay');
        fdump_api(['msg' => '支付方法',  'config' => $pay_method['alipay']['config'], 'house_session' => $this->house_session], 'smsBuy/alipay_return_log', 1);
        $pay_class = new Alipay('','',$order_type,$pay_method['alipay']['config'],$this->house_session,0);
        $go_query_param = $pay_class->query_order();
        fdump_api(['msg' => '支付订单查询',  'go_query_param' => $go_query_param], 'smsBuy/alipay_return_log', 1);
        if($go_query_param['error'] === 0){
            switch($order_type){
                case 'sqrecharge':
                    fdump_api(['msg' => '回调',  'order_param' => $go_query_param['order_param']], 'smsBuy/alipay_return_log', 1);
                    D('Village_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
            }
        }
        switch($order_type){

            case 'sqrecharge':
                $redirctUrl = C('config.site_url').'/shequ.php?g=House&c=Village_money&a=recharge&order_id='.$now_village['order_id'];
                break;
        }
        fdump_api(['msg' => '返回条状页面链接',  'redirctUrl' => $redirctUrl], 'smsBuy/alipay_return_log', 1);
        redirect($redirctUrl);
    }

}
?>