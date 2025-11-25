<?php
class Recruit_score_package_orderModel extends Model
{
    public function get_order($order_id){
        return $this->where(array('order_id'=>$order_id))->find();
    }
    public function get_pay_order($order_id){
        $now_order = $this->get_order($order_id);
        $order_info = array(
            'pay_offline'           => false,         //线下支付
            'pay_merchant_balance'  => false,         //商家余额
            'pay_merchant_coupon'   => false,         //商家优惠券
            'pay_merchant_ownpay'   => false,         //商家自有支付
            'pay_system_balance'    => true,          //平台余额
            'pay_system_coupon'     => false,         //平台优惠券
            'pay_system_score'      => false,         //平台积分抵现
            'order_info'            => $now_order,    //平台积分抵现
        );

        return $order_info;
    }
    
    public function get_order_url($order_id,$is_mobile){
        $now_order = $this->get_order($order_id);
        return $this->config['site_url'].'/wap.php?g=Wap&c=My&a=integral';
    }

    public function after_pay($order_id,$plat_order_info){
        $now_order = $this->get_order($order_id);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        //修改订单状态
        $data = array(
            'paid' => 1,
            'pay_time' => time(),
            'pay_type' => $plat_order_info['pay_type'],
        );
        $res = $this->where(array('order_id'=>$order_id))->save($data);

        $score_package = M('Recruit_score_package')->where(array('package_id'=>$now_order['package_id']))->find();

        // 增加积分
        $res = D('User')->add_score($now_order['uid'],$now_order['score'],'购买积分套餐【'.$score_package['name'].'】，增加积分');
    }

    public function get_limit_list_page($condition = array(),$pageSize = 20,$is_wap=0)
    {
        $return = array();
        $condition_where = '1';

        if($condition['uid']){
            $condition_where .= ' AND o.uid in (' . $condition['uid'] . ')';
        }

        if($condition['name']){
            $condition_where .= ' AND o.name like "%' . $condition['name'] . '%"';
        } 

        if($condition['nickname']){
            $condition_where .= ' AND u.nickname like "%' . $condition['nickname'] . '%"';
        }

        if($condition['phone']){
            $condition_where .= ' AND u.phone like "%' . $condition['phone'] . '%"';
        } 

        if($condition['package_id']){
            $condition_where .= ' AND p.package_id in (' . $condition['package_id'] . ')';
        }

        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }

        $field = 'o.*,u.nickname,u.phone,p.name';
        $order = ' `o`.`order_id` DESC';
        $join = 'as o LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_score_package as p on `p`.`package_id` = `o`.`package_id`';
        $count = $this->join($join)->where($condition_where)->count();

        if ($is_wap==1) {
            $_GET['page'] = intval($_GET['page']) ? intval($_GET['page']) : 1;
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit(($_GET['page']-1)*$pageSize.','.$pageSize)->select();
        }else{
            import('@.ORG.system_page');
            $p = new Page($count,$pageSize,'page');
            $list = $this->field($field)->join($join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
            $return['pagebar'] = $p->show();
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['page'] = $_GET['page'];
        $return['list'] = $list ? $list : array();
        return $return;
    }
}