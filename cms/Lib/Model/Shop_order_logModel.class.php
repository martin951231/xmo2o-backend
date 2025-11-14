<?php
//
class Shop_order_logModel extends Model
{

    public function add_log($param,$is_pick_in_store=1)
    {
        if (empty($param['order_id']))
            return false;
        if ($order = D('Shop_order')->field(true)->where(array('order_id' => $param['order_id']))->find()) {
            $not_change_deliver_status = $param['not_change_deliver_status']??0;
            // 状态(0：用户下单，1：用户支付，2：店员接单，3：配送员接单，4：配送员取货，5：配送员送达，6：配送结束，7：店员验证消费，8：用户完成评论，9用户退款，10用户取消订单)
            $data = array('dateline' => time());
            $data['order_id'] = intval($param['order_id']);
            $data['status'] = isset($param['status']) ? intval($param['status']) : 0;
            $data['phone'] = isset($param['phone']) ? $param['phone'] : '';
            $data['name'] = isset($param['name']) ? $param['name'] : '';
            $data['note'] = isset($param['note']) ? $param['note'] : '';
            $data['ext'] = isset($param['ext']) ? $param['ext'] : '';
            $data['from_type'] = isset($param['from_type']) ? intval($param['from_type']) : 0;
            if ($order['order_from'] == 6 && $data['status'] > 1) return false;
            if ($data['status'] == 9 && $not_change_deliver_status == 0) {
                D('Deliver_supply')->where(array('order_id' => $data['order_id'], 'item' => 2))->save(array('status' => 0));
            }
            if ($order['cartid'] && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $order['cartid']))->find())) {
                //0:拼单中，1:拼单已锁单
                switch (intval($data['status'])) {
                    case 1:
                        if ($orderTemp['status'] < 2) {//2支付成功等待接单
                            D('Shop_order_temp')->where(array('cartid' => $order['cartid']))->save(array('status' => 2));
                        }
                        break;
                    case 2:
                        if ($orderTemp['status'] < 3) {//3订单已接单在配送中
                            D('Shop_order_temp')->where(array('cartid' => $order['cartid']))->save(array('status' => 3));
                        }
                        //店员接单后写入限时优惠统计日志
                        D('Time_limited_discount_goods')->write_log($data['order_id']);
                        break;
                    case 6:
                    case 7:
                        if ($orderTemp['status'] < 4) {//4订单完成
                            D('Shop_order_temp')->where(array('cartid' => $order['cartid']))->save(array('status' => 4));
                        }
                        D('Time_limited_discount_goods')->write_log($data['order_id']);
                        break;
                    case 9:
                        if ($orderTemp['status'] < 5) {//5订单退款
                            D('Shop_order_temp')->where(array('cartid' => $order['cartid']))->save(array('status' => 5));
                        }
                        break;
                    case 10:
                        if ($orderTemp['status'] < 6) {//6订单取消
                            D('Shop_order_temp')->where(array('cartid' => $order['cartid']))->save(array('status' => 6));
                        }
                        break;
                }
                
            }else{
                if (in_array($data['status'], [2, 7])) {
                    D('Time_limited_discount_goods')->write_log($data['order_id']);
                }
            }
            $now_user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
            if ($now_user['openid'] && $data['status'] != 0 && $data['status'] != 1 && $order['order_from'] != 6) {
                $status = array(
                    L_('下单成功'),
                    L_('支付成功'),
                    L_('店员已接单'),
                    L_('配送员已接单'),
                    L_('配送员已到店'),
                    L_('配送员配送中'),
                    L_('配送结束'),
                    L_('店员验证消费'),
                    L_('完成评论'),
                    L_('已完成退款'),
                    L_('已取消订单'),
                    L_('商家分配自提点'),
                    L_('商家发货到自提点'),
                    L_('自提点已接货'),
                    L_('自提点已发货'),
                    L_('您在自提完成取货'),
                    30 => L_('店员修改价格'),
                    31 => L_('由于配送员暂时无法配送，已将该订单重新分配到配送员抢单池，请耐心等待其他配送员抢单'),
                    34 => L_('分配配送员'),
                    35 => L_('配送员已接单'),
                    36 => L_('更换配送员')
                );
                if($order['order_from']==1){
                    $href = C('config.site_url') . '/wap.php?c=Mall&a=status&order_id=' . $order['order_id'] . '&mer_id=' . $order['mer_id'] . '&store_id=' . $order['store_id'];
                    $alias_name=C('config.mall_alias_name');
                    $alias_name=$alias_name?$alias_name:L_('商城');
                    if($is_pick_in_store==3){
                        $status['2']=L_('店员已发货');    
                    }
                }else{
                    $href = C('config.site_url') . '/wap.php?c=Shop&a=status&order_id=' . $order['order_id'] . '&mer_id=' . $order['mer_id'] . '&store_id=' . $order['store_id'];
                    $alias_name=C('config.shop_alias_name');
                }
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $first=$alias_name . L_('订单');
                if($order['group_id']>0){
                    $first=$alias_name.L_('配送状态变更提醒');
                }
                $fetchNumber = '';
                if(in_array($data['status'],[2,11])){
                    if($order['is_pick_in_store'] == 2 && $order['fetch_number']){
                        $fetchNumber = '(取单号：'.$order['fetch_number'].')';
                        if(C('customization.shop_order_fetch_number_prefix')){
                            $fetchNumber = '(取单号：'.C('customization.shop_order_fetch_number_prefix').$order['fetch_number'].')';
                        }
                    }
                }
                
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' => $status[$data['status']],
                    'OrderSn' => $order['real_orderid'],
                    'OrderStatus' => $first,
                    'remark' => $fetchNumber.date('Y-m-d H:i:s')
                ), $order['mer_id']);
            }
            if ($data['status'] != 0) {
                import('@.ORG.Apppush');
                $order['status'] = $data['status'];
                $apppush = new Apppush();
                $apppush->send($order, 'shop');
            }
            
            if (intval($data['status']) == 1 && intval($order['paid']) == 1) {
                if($order['order_from']==1 && $order['group_id']>0){    //商城拼团中订单不发送消息给店员,等成团后统一发给店员

                }else{
                    D('Merchant_store_staff')->sendMsg($order['store_id'],$order);    
                }
            }

            if (intval($data['status']) == 2 && $order['platform'] == 0) {
                //店员接单的时候打印，只打印本平台的单子，饿了么，美团的单子不打印
                $printHaddle = new PrintHaddle();
                $printHaddle->printit($order['order_id'], 'shop_order', 5);
            }

            //自动接单发送消息提醒
            if (intval($data['status']) == 2 && $param['is_auto_accept_order']) {
                D('Merchant_store_staff')->sendMsg($order['store_id'], $order, true);
            }


            //分配配送员的，入库时依然存3，因为前端页面都是按3再来判断的。
            if($data['status'] == 34){
                $data['status'] = 3;
            }

            //取消订单
            if ($data['status'] == 10) {
                (new Deliver_supplyModel())->order_cancel_notice($param['order_id'], 'shop');
            }


            //订单退款退还优惠券
            if ($order['paid'] == 1 && ($data['status'] == 9 || $data['status'] == 10)) {
                fdump_sql([$data,$_SERVER['REQUEST_URI'],$order['card_id']],'shop_order_log');
                $refundCouponRule = !is_null(C('config.shop_order_refund_coupon_rule'));
                $receiveLog = $this->where(['order_id' => $data['order_id'], 'status' => 2])->find();
                $useLog = $this->where(['order_id' => $data['order_id'], 'status' => 7])->find();
                $backCoupon = false;
                if ($refundCouponRule == 0 && empty($receiveLog)) {
                    $backCoupon = true;
                } else if ($refundCouponRule == 1 && empty($useLog)) {
                    $backCoupon = true;
                }

                if ($backCoupon && ($order['coupon_id'] > 0 || $order['card_id'] > 0)) {
                    if ($order['coupon_id'] > 0) {
                        M('System_coupon_hadpull')->where(['uid' => $order['uid'], 'id' => $order['coupon_id']])->save(['is_use' => 0, 'use_time' => 0]);
                    }
                    if ($order['card_id'] > 0) {
                        M('Card_new_coupon_hadpull')->where(['uid' => $order['uid'], 'id' => $order['card_id']])->save(['is_use' => 0, 'use_time' => 0]);
                    }
                }
            }
            return $this->add($data);
        }
    }

    //获取订单最新的状态信息
    public function get_last_log($order_id,$to=''){
        $info = $this->where(array('order_id' => $order_id))->order('id DESC')->find();
        return $to?$this->$to($info):$info;
    }

    private function to_wap_usr($info){
        $order = M('Shop_order')->field(true)->where(['order_id'=>$info['order_id']])->find();
        if ($order['is_pick_in_store'] == 3) {
            $express = D('Express')->get_express($order['express_id']);
            $order['express_name'] = isset($express['name']) ? $express['name'] : '';
            $order['express_code'] = $express['code'];
        }
        $html = '';
        switch ($info['status']) {
            case '0':
                $html = '<h2>订单生成成功</h2> <p>订单编号：'.$order['real_orderid'].'</p>';
                break;
            case '1':
                $html = '<h2>订单支付成功</h2> <p>订单编号：'.$order['real_orderid'].'</p>';
                break;
            case '2':
                if($info['note']=='update'){
                    $html = '<h2>店员已修改快递信息</h2><p>已将快递公司更改为<strong style="color:red">【'.$order['express_name'].'】</strong>，快递单号:<strong style="color:green">'.$order['express_number'].'</strong></p>';
                }elseif($info['note']=='订单已确认，商家备货中'){
                    $html = '<h2>订单已确认，商家备货中</h2> <p>订单编号：'.$order['real_orderid'].'</p>';
                }elseif($order['is_pick_in_store']==3){
                    $html = '<h2>店员已发货</h2><p>已发货给快递公司<strong style="color:red">【'.$order['express_name'].'】</strong>，快递单号:<strong style="color:green">'.$order['express_number'].'</strong></p>';
                }else{
                    $html = '<h2>店员接单</h2> <p>正在为您准备商品</p>';
                }
                break;
            case '3':
                $html = '<h2>配送员接单</h2> <p>配送员正在赶往店铺取货</p>';
                break;
            case '4':
                $html = '<h2>配送员取货</h2> <p>已取货，准备配送，请耐心等待</p>';
                break;
            case '5':
                $html = '<h2>配送员配送中</h2> <p>配送员正快速向您靠拢，请耐心等待！</p>';
                break;
            case '6':
                if($order['is_pick_in_store']<2){
                    $html = '<h2>订单已完成</h2><p>配送员已完成配送，欢迎下次光临！</p>';
                }else{
                    $html = '<h2>订单已完成</h2><p>订单编号：'.$order['real_orderid'].'</p>';
                }
                break;
            case '7':
                if($order['is_pick_in_store']==3){
                    $html = '<h2>店员已发货</h2> <p>已发货给快递公司<strong style="color:red">【'.$order['express_name'].'】</strong>，快递单号:<strong style="color:green">'.$order['express_number'].'</strong></p>';
                }else{
                    $html = '<h2>店员验证消费</h2> <p>订单改成已消费</p>';
                }
                break;
            case '8':
                $html = '<h2>完成评论</h2> <p>您已完成评论，谢谢您提出宝贵意见！</p>';
                break;
            case '9':
                if($info['note']){
                    $html = '<h2>已完成退款</h2>'.'<p>'.$info['note'].'</p>';
                }else{
                    $html = '<h2>已完成退款</h2>'.'<p>您已完成退款</p>';
                }
                break;
            case '10':
                if($info['note']){
                    $html = '<h2>已取消订单</h2>'.'<p>'.$info['note'].'</p>';
                }else{
                    $html = '<h2>已取消订单</h2>'.'<p>您已取消订单</p>';
                }
                break;
            case '11':
                $html = '<h2>商家分配自提点</h2> <p>店员给您分配了自提点</p>';
                break;
            case '12':
                $html = '<h2>商家发货到自提点</h2> <p>店员已经给您发货到配送点</p>';
                break;
            case '13':
                $html = '<h2>自提点已接货</h2> <p>自提点已经接到您的货物了</p>';
                break;
            case '14':
                $html = '<h2>自提点已发货</h2> <p>自提点已经给您发货了</p>';
                break;
            case '15':
                $html = '<h2>您在自提点取货</h2> <p>您在自提点已经把您的货提走了！</p>';
                break;
            case '30':
                $html = '<h2>店员为您修改了价格</h2> <p>店员已将订单的总价修改成'.$info['note'].'</p>';
                break;
            case '31':
                $html = '<h2>配送员放弃配送</h2> <p>'.$info['note'].'</p>';
                break;
            case '34':
                $html = '<h2>订单已被取消</h2> <p>无法达成配送，已为您取消订单</p>';
                break;
            case '41':
                $html = '<h2>退款申请中</h2> <p>订单编号：'.$order['real_orderid'].'</p>';
                break;
            case '42'://撤销申请退款的状态，
                $html = '<h2>退款申请撤回成功</h2> <p>订单将继续进行中</p>';
                break;
            default:
                $html = 'ERROR:订单状态数据错误！';
                break;
        }
        $return =[
            'html'=>$html,

            'dateline'=>date('Y-m-d H:i:s',$info['dateline']),
        ];
        //dump($return);exit;
        return $return;
    }

    //新增批量导入失败日志
    public function add_goods_batch_fail_log($data){
        M('Shop_goods_batch_fail_log')->add($data);
    }
   //新增批量导入日志
    public function add_goods_batch_log($data){
        $logId=M('Shop_goods_batch_log')->add($data);
        return $logId;
    }
   //修改批量导入日志状态
    public function save_goods_batch_log($logId,$data){
        M('Shop_goods_batch_log')->where(['pigcms_id' => $logId])->save($data);
    }

    //批量导入日志列表
    public function goods_batch_log_list($where){
        $return= M('Shop_goods_batch_log')->where($where)->order('pigcms_id desc')->select();
        return $return;
    }
    //批量导入失败日志列表
    public function goods_batch_fail_log_list($where){
        $return= M('Shop_goods_batch_fail_log')->where($where)->select();
        return $return;
    }
}

?>