<?php
//订单详情
class OrderdetailAction extends BaseAction{
    public function store_detail(){
        $where['order_id'] = $_GET['order_id'];
        $order = M('Store_order s')->field('s.*,u.nickname,u.phone')->join(C('DB_PREFIX').'user u ON s.uid = u.uid ')->where($where)->find();
        $pay_method = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'], 0, $order);
        $order['internal_card_pay_money'] = 0;
        if (!empty($order['internal_card_pay'])) {
            $internalCardPayMoney = 0;
            $internalCardPay = @json_decode($order['internal_card_pay'], true);
            if (is_array($internalCardPay)) {
                $internalCardPayMoney = array_sum($internalCardPay);
            }
            $order['internal_card_pay_money'] = $internalCardPayMoney;
        }
        $this->assign('order',$order);
        $this->assign('pay_method',$pay_method);
        $this->display();
    }

    public function wxapp_detail(){
        $where['order_id'] = $_GET['order_id'];
        $order = M('Wxapp_order w')->field(true)->where($where)->find();
        $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
        $this->assign('order',$order);
        $this->assign('pay_method',$pay_method);
        $this->display();
    }

    public function weidian_detail(){
        $where['order_id'] = $_GET['order_id'];
        $order = M('Weidian_order w')->field(true)->join(C('DB_PREFIX').'user u ON w.uid = u.uid ')->where($where)->find();
        $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
        $this->assign('order',$order);
        $this->assign('pay_method',$pay_method);
        $this->display();
    }

    public function yydb_detail(){
        $where['pigcms_id'] = $_GET['order_id'];
        $order = M('Extension_activity_list e')->field(true)->join(C('DB_PREFIX').'user u ON e.lottery_uid = u.uid ')->where($where)->find();
        $this->assign('order',$order);
        $this->display();
    }

    public function coupon_detail(){

        $condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');
        $condition_where = "`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='{$_GET['mer_id']}' AND `ecr`.`pigcms_id`='{$_GET['order_id']}' AND `ear`.`uid`=`u`.`uid`";
        $now_order = D('')->field('`ecr`.`pigcms_id`,`ecr`.`number`,`eal`.`title`,`ear`.`uid`,`eal`.`pigcms_id` as id,`eal`.`money`,`eal`.`name`,`u`.`nickname`,`u`.`phone`,`ecr`.`check_time`,`ecr`.`last_staff`')->where($condition_where)->table($condition_table)->find();
        $this->assign('order',$now_order);
        $this->display();
    }

    public function moblie_order_detail(){
        $now_order = D('Mobile_recharge_order')->get_order_by_id($_GET['order_id']);
        $order = D('Plat_order')->get_order_by_business_id(array('business_type'=>'mobile_recharge','business_id'=>$_GET['order_id']));

        switch($now_order['status']){
            case 3:
                $now_order['status_txt']  = '您的订单已提交成功，正在等待服务商充值';
                break;
            case 4:
                $now_order['status_txt']  = '充值成功';
                break;
            case 5:
            case 6:
                $now_order['label'] = unserialize($now_order['label']);
                $now_order['status_txt']  = $now_order['label']['msg'];
                break;
            case 12:
                $now_order['status_txt']  = '充值失败';
                break;
        }
 
        $this->assign('order',$order);
        $this->assign('now_order',$now_order);
        $this->display();
    }

    public function un_normal_order(){
        $order_type=array(
            'all'=>'选择分类',
            'group'=>C('config.group_alias_name'),
            'shop'=>C('config.shop_alias_name'),
            'meal'=>C('config.meal_alias_name'),
            'appoint'=>C('config.appoint_alias_name'),
            'waimai'=>'外卖',
            'store'=>C('config.cash_alias_name'),
            'cash'=>'到店支付',
            'weidian'=>'微店',
            'wxapp'=>'营销',
            'withdraw'=>'提现',
            'coupon'=>'优惠券',
            'withdraw'=>'提现',
            'activity'=>'平台活动',
            'spread'=>'商家推广',
            'sub_card'=>'免单套餐'
        );
        $count = M('Payment_money_order')->where('payment_money<>paid_money AND paid_money>0 ' )->count();
        import('@.ORG.system_page');
        $p = new Page($count,50);
        $money_list = M('Payment_money_order')->where('payment_money<>paid_money')->order('id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($money_list as $vo) {
            $vo['order_type'] = $order_type[$vo['order_type']];
            $res = M('Fdump_sql')->where(array('data'=>array('like','%'.$vo['orderid'].'%')))->find();
            $vo['pay_type'] ='';
        }
        $this->assign('money_list',$money_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->display();

    }
}