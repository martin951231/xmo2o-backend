<?php

/*
 * 校园充值
 *
 */
class Company_campusAction extends BaseAction
{
    public function sweepCode()
    {
        if (empty($this->user_session)) {
            $this->error_tips(L_('请先进行登录！'), U('Login/index'));
        }
        $uid = $this->user_session['uid'];
        $nowUser = D('User')->get_user($uid);
        $staff = D('Qiye')->getStaff($uid);
        if($staff){
            $stores = M('Merchant_store')->alias('s')
                ->join(C('DB_PREFIX') . 'qiye_merchant AS qm ON qm.mer_id = s.mer_id')
                ->join(C('DB_PREFIX') . 'merchant AS m ON qm.mer_id = m.mer_id')
                ->where(['qm.qiye_id' => $staff['qiye_id'], 'm.status' => 1, 's.status' => 1])
                ->field('s.name')
                ->select();
        }

        //企业余额和平台余额均为0，不展示二维码
        if($staff['money'] + $nowUser['now_money'] < 0.01){
            $payCode = '';
        }else{
            $payCode = $this->ajaxBuildPayCode()['retval'];
        }
        $this->assign('payCode', $payCode);
        $this->assign('nowUser', $nowUser);
        $this->assign('staff', $staff);
        $this->assign('stores', $stores);
        $this->display();
    }

    public function ajaxBuildPayCode()
    {
        $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => []];
        try {
            if (empty($this->user_session)) {
                throw new \Exception(L_('请先进行登录！'));
            }
            $uid = $this->user_session['uid'];
            $tm = time();
            $payCode = [
                'uid' => $uid,
                'code' => '90' . createRandomStr(16, true),
                'time' => $tm,
                'expire_time' => $tm + 60
            ];
            $id = M('Qiye_pay_code_record')->add($payCode);
            //重新生成支付码，则之前的旧支付码全部失效
            M('Qiye_pay_code_record')->where(['uid' => $uid, 'status' => 0, 'id' => ['neq', $id]])->save(['status' => 2]);
            $rs['retval']['code'] = $payCode['code'];

            //$rs['retval']['pay_qr_code'] = "http://qr.liantu.com/api.php?w=200&m=10&text=" . $payCode['code'];
            $rs['retval']['pay_qr_code'] = C('config.site_url') . str_replace('wap.php', 'index.php', U('Index/Recognition/get_own_qrcode', array('qrCon' => $payCode['code'])));
            $rs['retval']['pay_bar_code'] = $this->config['site_url'] . U('My/my_store_card_barcode', array('code_info' => $payCode['code']));
        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = L_('获取二维码/条形码失败,请刷新重试');
        }
        if (IS_AJAX) {
            $this->ajaxReturn($rs);
        } else {
            return $rs;
        }
    }

    public function checkLogin(){
        if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }
    }
    
    // 选择商家
    public function searchSchool() {
        if (!$this->config['open_company_advance_deposit']) {
           $this->error_tips(L_('功能未开启'));
        }
        $this->display();
    }

    public function ajaxList()
    {
        $page = $_POST['page'] ? $_POST['page'] : 1;
        $start = ($page - 1) * 20;
        $keyword = $_POST['keyword'];

        if (!$this->config['open_company_advance_deposit']) {
            exit(json_encode(array('page' => $page, 'list' => array())));
        }
        $where = array();
        $where['status'] = 1;
        $where['open_school'] = 1;
        if ($keyword) {
            $where['name'] = array('like', '%' . $keyword . '%');
        }
        $list = M('Merchant')->where($where)->limit($start, 20)->order('mer_id DESC')->select();
        $list = $list ? $list : array();
        exit(json_encode(array('page' => $page, 'list' => $list)));
    }

    // 订单列表
    public function rechargeRecord() {
        $this->checkLogin();
        $this->display();
    }

    // 获取订单列表
    public function ajaxOrderList(){
        $page = $_POST['page'] ? $_POST['page'] : 1;
        $start = ($page-1) * 10;
        $where = array();
        $where['o.paid'] = 1;
        $where['o.uid'] = $this->user_session['uid'];
        $list = M('Merchant_user_recharge_order')->join('as o left join '.C('DB_PREFIX').'card_userlist as c on o.card_id=c.id')->field('o.*,c.name')->where($where)->limit($start,10)->order('o.pay_time DESC')->select();
        foreach ($list as &$value) {
            $value['card_info'] = $value['name'].'（'.($value['physical_id'] ? $value['physical_id'] : $value['card_id']).'）';
            $value['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
        }
        $list = $list ? $list : array();
        exit(json_encode(array('page'=>$page,'list'=>$list)));
    }

    // 充值页面
    public function recharge() {
        $this->checkLogin();
        if (!$this->config['open_company_advance_deposit']) {
           $this->error_tips(L_('功能未开启'));
        }
        if (!$_GET['mer_id']) {
           $this->error_tips(L_('未选择商家'));
        }
        $merchant = M('Merchant')->where(array('mer_id'=>$_GET['mer_id']))->find();
        if (!$merchant['open_school']) {
           $this->error_tips(L_('功能未开启'));
        }

        $merchant_recharge_agreement = htmlspecialchars_decode(htmlspecialchars_decode($this->config["merchant_recharge_agreement"]));
        $merchant_recharge_agreement = str_replace("\r\n", "<br>", $merchant_recharge_agreement);
        $merchant_recharge_agreement = str_replace("\n", "<br>", $merchant_recharge_agreement);
        $this->assign('merchant',$merchant);
        $this->assign('merchant_recharge_agreement',$merchant_recharge_agreement);

        $this->display();
    }

    // 充值
    public function do_recharge() {
        if (!$this->config['open_company_advance_deposit']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('功能未开启'))));
        }

        if (!$this->user_session['uid']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('未登录'),'url'=>U('Login/index'))));
        }
        if (!$_POST['mer_id']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('参数传递错误'))));
        }
        if (!$_POST['name']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入姓名'))));
        }
        if (!$_POST['card_id']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入卡号/学号/工号'))));
        }
        if ($_POST['money']<=0) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入正确的充值金额'))));
        }

        // 查询会员卡
        $where = array();
        $where['name'] = $_POST['name'];
        $where['physical_id'] = $_POST['card_id'];//实体卡号
        $where['mer_id'] = $_POST['mer_id'];
        $card_info = M('Card_userlist')->where($where)->find();
        if (!$card_info) {
           exit(json_encode(array('err_code'=>1,'msg'=>L_('填写的人员信息有误，请修改再试'))));
        }
        if ($card_info['status'] == 0) {
           exit(json_encode(array('err_code'=>1,'msg'=>L_('填写的人员信息有误，请修改再试'))));
        }

        $merchant = M('Merchant')->where(array('mer_id'=>$where['mer_id']))->find();
        if (!$merchant['open_school']) {
            exit(json_encode(array('code'=>1,'msg'=>L_('功能未开启'))));
        }
        $data['money'] = getFormatNumber($_POST['money']);
        $data['card_id'] = $card_info['id'];
        $data['orderid'] = build_real_orderid($this->user_session['uid']);
        $data['uid'] = $this->user_session['uid']; 
        $data['mer_id'] = $card_info['mer_id']; 
        $data['add_time'] = time();
        $data['last_time'] = time();

        if ($order_id = D('Merchant_user_recharge_order')->add($data)) {
            $pay_order_param = array(
                'business_type' => 'merchant_user_recharge',
                'business_id' => $order_id,
                'order_name' => '商家会员卡充值订单',
                'uid' => $this->user_session['uid'],
                'total_money' =>  $data['money'],
            );
            $result = D('Plat_order')->add_order($pay_order_param);
            if($result['error_code']){
                exit(json_encode(array('err_code' => 1, 'msg' => L_('充值失败，稍后重试！'))));
            }else{
                exit(json_encode(array('err_code' => 0, 'url' => U('Pay/check', array('order_id' => $result['order_id'], 'type' => 'plat')))));
            }
        } else {
            exit(json_encode(array('err_code' => true, 'msg' => L_('充值失败，稍后重试！'))));
        }

        $this->display();
    }

    // 充值
    public function get_card_info() {
        if (!$this->config['open_company_advance_deposit']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('功能未开启'))));
        }

        if (!$this->user_session['uid']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('未登录'),'url'=>U('Login/index'))));
        }
        if (!$_POST['mer_id']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('参数传递错误'))));
        }
        if (!$_POST['name']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入姓名'))));
        }
        if (!$_POST['card_id']) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入卡号/学号/工号'))));
        }
        if ($_POST['money']<=0) {
           exit(json_encode(array('code'=>1,'msg'=>L_('请输入正确的充值金额'))));
        }

        // 查询会员卡
        $where = array();
        $where['name'] = $_POST['name'];
        $where['physical_id'] = $_POST['card_id'];//实体卡号
        $where['mer_id'] = $_POST['mer_id'];
        $card_info = M('Card_userlist')->where($where)->find();
        if (!$card_info) {
           exit(json_encode(array('err_code'=>1,'msg'=>L_('填写的人员信息有误，请修改再试'))));
        }
        if ($card_info['status'] == 0) {
           exit(json_encode(array('err_code'=>1,'msg'=>L_('填写的人员信息有误，请修改再试'))));
        }
        $card_group = M('Card_group')->where(array('id'=>$card_info['gid']))->find();
        $card_info['group_name'] = $card_group['name'] ? $card_group['name'] : '无'; 
        $card_info['money_total'] = getFormatNumber($card_info['card_money'] + $card_info['card_money_give']); 
        exit(json_encode(array('err_code'=>0,'data'=>$card_info)));
    }
    
    public function getPayResult()
    {
        $authCode = isset($_GET['auth_code']) ? trim($_GET['auth_code']) : '';
        $retval = [];
        if (empty($authCode)) {
            $this->returnCode(1, [], L_('付款码为空'));
        } else {
            $payCode = M('Qiye_pay_code_record')->where(['code' => $authCode])->find();
            if (empty($payCode)) {
                $this->returnCode(1, [], L_('付款码不存在'));
            }
            $orderid = $payCode['order_id'];
            if (empty($orderid)) {
                $this->returnCode(1, [], L_('付款码未使用'));
            }
            $split = explode('_', $orderid);
            $prefix = $split[0];
            if ($payCode['type'] == 'micro') {
                $order = M('Micropay_order')->where(['orderid' => $orderid])->find();
                if ($order['status'] == 1) {
                    $payPrice = $order['total_fee'];
                    $retval = [
                        'price' => sprintf('%.2f', $order['total_fee'])
                    ];
                } else {
                    $this->returnCode(1, [], L_('付款码未使用'));
                }
            } else if ($payCode['type'] == 'store') {
                $order = M('Store_order')->where(['order_id' => $orderid])->find();
                if ($order['paid'] == 1) {
                    $payPrice = $order['qiye_pay']+$order['balance_pay'];
                    $retval = [
                        'price' => sprintf('%.2f', $order['qiye_pay'])
                    ];
                } else {
                    $this->returnCode(1, [], L_('付款码未使用'));
                }
            } else {
                $order = M('Shop_order')->where(['order_id' => $orderid])->find();
                if ($order['paid'] == 1) {
                    $payPrice = $order['qiye_pay']+$order['balance_pay'];
                    $retval = [
                        'price' => sprintf('%.2f', $order['qiye_pay'])
                    ];
                } else {
                    $this->returnCode(1, [], L_('付款码未使用'));
                }
            }
            $nowMoney = M('User')->where(['uid' => $payCode['uid']])->getField('now_money');
            $qiyeMoney = M('Qiye_staff')->where(['job_number' => $payCode['uid']])->getField('money');
            $retval = [
                'pay_money' => sprintf('%.2f', $payPrice),
                'now_money' => sprintf('%.2f', $nowMoney),
                'qiye_money' => sprintf('%.2f', $qiyeMoney),
            ];
            $this->returnCode(0, $retval);
        }
    }
}
