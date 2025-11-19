<?php

class Store_orderAction  extends  BaseAction{
    public function index(){
        $store_order = M('Store_order');
        import('@.ORG.merchant_page');
        $where = array('paid' => 1); 
        $where['s.mer_id']  =$this->merchant_session['mer_id'];

        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $where['order_id'] = $_GET['keyword'];
            }else if ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'name') {
                $where['u.nickname'] =array('like',"%{$_GET['keyword']}%");
            } elseif ($_GET['searchtype'] == 'phone') {
                $where['u.phone']=$_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'third_id') {
                $where['s.third_id']=$_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'store_name') {
                $where['ms.name'] =array('like',"%{$_GET['keyword']}%");
            }
        }

		if ($_GET['begin_time'] && $_GET['end_time']) {
            $where['s.pay_time'] = array(array('gt', strtotime($_GET['begin_time'])), array('lt', strtotime($_GET['end_time'] . '23:59:59')));
        }

        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($pay_type && $pay_type != 'balance') {
            if(strpos($pay_type,'App')){
                preg_match('/(\w+)App/',$pay_type,$res);
                $where['_string'] = " (`s`.`pay_type`='" . $res[1] . "' AND s.is_mobile_pay=2) ";
            }else{
                $where['_string'].= " ( `s`.`pay_type`='" . $pay_type . "')";
            }
        } else if ($pay_type == 'balance') {
            $where['_string'] = "(`s`.`balance_pay`<>0 OR `s`.`merchant_balance` <> 0)";
        }


        $count =  M('Store_order')->join("AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid")
            ->where($where)->count();
        $p = new Page($count, 10);
        
        $order_list = M('Store_order')->join("AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid")
            ->field(' s.*, u.nickname, u.phone, m.name AS merchant_name, ms.name AS store_name')->where($where)->order('s.pay_time desc')->limit($p->firstRow,$p->listRows)->select();

        $all_money = M('Store_order')->join("AS s LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid")
            ->where($where)->sum('s.price');
        foreach ($order_list as &$l) {
            $l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay'], $l['paid'], 0, $l);
        }
        $pagebar = $p->show();

        $pay_method = D('Config')->get_pay_method('','',1);
        $pay_method['alipayApp'] = array(
            'name'=>L_('支付宝APP')
        );
        $pay_method['weixin']['name'] =L_('微信WAP支付');
        $pay_method['alipayh5']['name'] =L_('支付宝WAP支付');

        $this->assign('pay_type',$_GET['pay_type']);

        $this->assign('pay_method',$pay_method);
        $this->assign('all_money',$all_money);

        $this->assign(array('order_list' => $order_list, 'pagebar' => $pagebar,'pay_method'=>$pay_method));
        $this->display();
    }

    public function export(){
        $param = $_POST;
        $param['type'] = 'store';
        $param['rand_number'] = time();
        $param['paid'] = 1;
        // $param['from_plat'] = 1;
        // $param['info_type'] = 1;
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
        die;
    }


    //商家退款
    public function refund_store_order()
    {
        $orderid = isset($_POST['orderid']) ? trim($_POST['orderid']) : 0;
        if (empty($orderid) || empty($this->merchant_session['mer_id'])) {
            $this->error(L_('参数有误'));
        }

        $sub_account_Id = 0;
        $mer_name = '商家:' . $this->merchant_session['name'];
        $v2o_ticket = $_COOKIE['merchant_access_token'] ? $_COOKIE['merchant_access_token'] : Token::getToken();
        if ($v2o_ticket) {
            import("@.ORG.Token");
            $ticket = Token::checkToken($v2o_ticket);
            if ($ticket && $ticket['memberId']) {
                $sub_account_Id = $ticket['extends']['mer_subaccount_id'] ?? 0;
            }
        }
        if($sub_account_Id > 0){
            $sub_account = D('Merchant_user_account')->where(['id'=>$sub_account_Id])->getField('account');
            $sub_account &&  $mer_name = '子账号:' . $sub_account;
        }

        try {
            D('Store_order')->refund_store_order($orderid, true, 0, $mer_name);
            $this->success('退款成功');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}