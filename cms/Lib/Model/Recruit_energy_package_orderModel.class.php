<?php
class Recruit_energy_package_orderModel extends Model
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
        return $this->config['site_url'].'/wap.php?g=Wap&c=Recruit&a=energy_package_order_list';
    }

    public function after_pay($order_id,$plat_order_info){
        fdump($plat_order_info,'recruit',true);
        $now_order = $this->get_order($order_id);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        //修改订单状态
        $data = array(
            'paid' => 1,
            'pay_time' => time(),
            'pay_type' => $plat_order_info['pay_type'],
        );
        
        $res = $this->where(array('order_id'=>$order_id))->save($data);
        if ($res) {
            //查询企业
            $now_company = M('Recruit_company')->where(array('uid'=>$now_order['uid']))->find();
            // 企业能量修改
            M('Recruit_company')->where(array('uid'=>$now_order['uid']))->setInc('couse_times',$now_order['couse_times']);
            M('Recruit_company')->where(array('uid'=>$now_order['uid']))->setInc('resume_times',$now_order['resume_times']);
            M('Recruit_company')->where(array('uid'=>$now_order['uid']))->setInc('invitation_times',$now_order['invitation_times']);
            if ($now_company['energy_expiration_time']<=0) {
               $now_order['active_time'] = time()+$now_order['active_time']*86400;
            }else{
                $now_order['active_time'] = $now_order['active_time']*86400;
            }
            M('Recruit_company')->where(array('uid'=>$now_order['uid']))->setInc('energy_expiration_time',$now_order['active_time']);

            $now_user = D('User')->get_user($now_order['uid']);

            $spread_total_money = $now_order['price'];
            //佣金
            /* 计算用户分享获得佣金 */
            if(!empty($now_user['openid'])&&C('config.recruit_spread_rate') ){
                //上级分享佣金
                $spread_rate = D('Percent_rate')->get_user_spread_rate(0,'recruit');

                $spread_users[]=$now_user['uid'];
                if($now_user['wxapp_openid']!=''){
                    $spread_where['_string'] = "openid = '{$now_user['openid']}' OR openid = '{$now_user['wxapp_openid']}' ";
                }else{
                    $spread_where['_string'] = "openid = '{$now_user['openid']}'";
                }
                $now_user_spread = D('User_spread')->field('`spread_openid`, `openid`,`is_wxapp`')->where($spread_where)->find();
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
                if(!empty($now_user_spread)){
                    if($now_user_spread['is_wxapp']){
                        $spread_user = D('User')->get_user($now_user_spread['spread_openid'],'wxapp_openid');
                    }else{
                        $spread_user = D('User')->get_user($now_user_spread['spread_openid'],'openid');
                    }
                    $user_spread_rate = $spread_rate['first_rate'];
                    if($spread_user && $user_spread_rate&&!in_array($spread_user['uid'],$spread_users)){
                       
                        $spread_money = round($spread_total_money * $user_spread_rate / 100, 2);

                        $spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'recruit','order_id'=>$now_order['order_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
                        if($spread_user['spread_change_uid']!=0){
                            $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
                        }
                        // 处理长订单id
                        if ($now_order['real_orderid']) {
                            $spread_data['real_orderid'] = $now_order['real_orderid'];
                        }
                        D('User_spread_list')->data($spread_data)->add();

                        $buy_user = D('User')->get_user($now_user_spread['openid'],'openid');
                        if($spread_money>0){
                            $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => $buy_user['nickname']."通过您的分享购买商品，验证消费后您将获得佣金。" , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
                        }
                        $spread_users[]=$spread_user['uid'];
                    }

                    //第二级分享佣金
                    if($spread_user['openid']!='' && $spread_user['wxapp_openid']!=''){
                        $spread_where['_string'] = "openid = '{$spread_user['openid']}' OR openid = '{$spread_user['wxapp_openid']}' ";
                    }elseif($spread_user['openid']!=''){
                        $spread_where['_string'] = "openid = '{$spread_user['openid']}' ";
                    }elseif($spread_user['wxapp_openid']!=''){
                        $spread_where['_string'] = "openid = '{$spread_user['wxapp_openid']}' ";
                    }
                    $second_user_spread = D('User_spread')->field('`spread_openid`, `openid`,`is_wxapp`')->where($spread_where )->find();
                    if(!empty($second_user_spread)) {
                        if($second_user_spread['is_wxapp']){
                            $second_user = D('User')->get_user($second_user_spread['spread_openid'], 'wxapp_openid');
                        }else{
                            $second_user = D('User')->get_user($second_user_spread['spread_openid'], 'openid');
                        }

                        $sub_user_spread_rate = $spread_rate['second_rate'];
                        if ($second_user && $sub_user_spread_rate&&!in_array($second_user['uid'],$spread_users)) {
                            $spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);

                            $spread_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'recruit', 'order_id' => $now_order['order_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
                            if($spread_user['spread_change_uid']!=0){
                                $spread_data['change_uid'] =    $second_user['spread_change_uid'];
                            }

                            D('User_spread_list')->data($spread_data)->add();
                            if($second_user_spread['openid']!=''){
                                $sec_where['_string'] = "openid = '{$second_user_spread['openid']}' OR wxapp_openid = '{$second_user_spread['openid']}' ";
                                $sec_user = M('User')->where($sec_where)->find();
                            }
                            if($spread_money>0) {
                                $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => $sec_user['nickname']."的子用户".$buy_user['nickname']."通过您的分享购买商品，验证消费后您将获得佣金。" , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
                            }

                            $spread_users[]=$second_user['uid'];
                        }

                        
                        //顶级分享佣金
                        if($second_user['openid']!='' && $second_user['wxapp_openid']!=''){
                            $spread_where['_string'] = "openid = '{$second_user['openid']}' OR openid = '{$second_user['wxapp_openid']}' ";
                        }elseif($second_user['openid']!=''){
                            $spread_where['_string'] = "openid = '{$second_user['openid']}' ";
                        }elseif($second_user['wxapp_openid']!=''){
                            $spread_where['_string'] = "openid = '{$second_user['wxapp_openid']}' ";
                        }
                        $first_user_spread = D('User_spread')->field('`spread_openid`, `openid`,`is_wxapp`')->where(    $spread_where)->find();

                        if (!empty($first_user_spread) && C('config.user_third_level_spread')) {
                            if($first_user_spread['is_wxapp']){
                                $first_spread_user = D('User')->get_user($first_user_spread['spread_openid'], 'wxapp_openid');
                            }else{
                                $first_spread_user = D('User')->get_user($first_user_spread['spread_openid'], 'openid');
                            }
                            // $sub_user_spread_rate = $update_group['third_spread_rate'] > 0 ? $update_group['third_spread_rate'] : C('config.user_second_spread_rate');
                            $sub_user_spread_rate = $spread_rate['third_rate'];
                            if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
                                $spread_money = round($spread_total_money* $sub_user_spread_rate / 100, 2);

                                $spread_data =array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'recruit', 'order_id' => $now_order['order_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
                                if($spread_user['spread_change_uid']!=0){
                                    $spread_data['change_uid'] =    $first_spread_user['spread_change_uid'];
                                }
                                // 处理长订单id
                                if ($now_order['real_orderid']) {
                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                                }

                                D('User_spread_list')->data($spread_data)->add();

                                if($first_user_spread['openid']!='') {
                                    $fir_where['_string'] = "openid = '{$first_user_spread['openid']}' OR wxapp_openid = '{$first_user_spread['openid']}' ";
                                    $fir_user = M('User')->where($fir_where)->find();
                                }
                                if($spread_money>0) {
                                    $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>$fir_user['nickname']."的子用户的子用户".$buy_user['nickname']."通过您的分享购买了商品，验证消费后您将获得佣金。" , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
                                }
                            }
                        }

                    }
                }

                $spread_condition['order_id'] = $now_order['order_id'] ;
                $spread_condition['order_type'] ='recruit' ;
                // 获得佣金
                $now_spread = D('User_spread_list')->where($spread_condition)->select();
                foreach($now_spread as $v){
                    $this->spread_check($v['uid'],$v['pigcms_id']);
                }
            }
        }
    }

    public function spread_check($uid,$id){
        $where = array(
            'pigcms_id'=>$id,
            '_string'=>'uid='.$uid.' OR change_uid='.$uid
        );

        $now_spread = D('User_spread_list')->where($where)->find();
        if($now_spread && $now_spread['status'] == 0){
            $order_info = $this->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
            if($order_info['paid'] == '1' ){
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                    if($now_spread['change_uid']!=0){
                        D('User')->add_money($now_spread['change_uid'],$now_spread['money'],"推广用户购买商品获得佣金（佣金过户）"); 
                    }else{
                        $res = D('User')->add_money($now_spread['uid'],$now_spread['money'],"推广用户购买商品获得佣金"); 
                        D('Scroll_msg')->add_msg('spread',$now_spread['uid'],'用户'.str_replace_name($now_spread['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'获得推广用户的消费佣金');    
                    }
                    return true;
                }else{
                    return false;
                }
            }
        }
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
        $join = 'as o LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid` = `u`.`uid` LEFT JOIN '.C('DB_PREFIX').'recruit_energy_package as p on `p`.`package_id` = `o`.`package_id`';
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