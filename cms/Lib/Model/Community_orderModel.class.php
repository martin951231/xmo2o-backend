<?php  
class Community_orderModel extends Model{
    public function get_pay_order($uid,$order_id){
        $now_order = $this->get_order_by_id($uid,$order_id);
        if($now_order['money']<=0){
            return array('error'=>1,'msg'=>L_('订单金额异常！'));
        }
        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单不存在！'));
        }
        
        return array('error'=>0,'order_info'=>$now_order);
    }

    // 获取订单信息
    public function get_order_by_id($uid,$order_id){
        $condition['uid'] = $uid;
        $condition['order_id'] = $order_id;
        return $this->field(true)->where($condition)->find();
    }

    // 获取订单信息
    public function after_pay($order_id,$case,$id){
        if (!$order_id) {
            return array('error_code' => true, 'msg' => L_('订单id不存在'));
        }
        $order_info = D('Community_order')->where(array('order_id'=>$order_id))->find();
        if (!$order_info&&$order_info['status']!=1) {
            return array('error_code' => true, 'msg' => L_('订单信息不存在或未支付'));
        }

        switch($case){
            case 'activity': // 群活动
                //报名id
                $join_id = $id;
                // 更改报名状态
                $upSql = D('Community_activity_join')->where(array('join_id'=>$join_id))->data(array('status'=>1,'order_id'=>$order_id))->save();
                if (!$upSql) {
                    return array('error_code' => true, 'msg' => L_('报名失败'));
                }
                return array('error_code' => false, 'msg' => L_('报名成功'));
            case 'joingroup': // 加群
                // 加群id
                $add_id = $id;

                // 查询加群信息
                $join_info = D('Community_join')->field(true)->where(array('add_id'=>$add_id))->find();
                if (!$join_info) {
                    return array('error_code' => true, 'msg' => L_('加群信息不存在'));
                }

                // 查询群信息
                $community_info = D('Community_info')->field(true)->where(array('community_id'=>$join_info['community_id']))->find();
                if (empty($community_info) || $community_info['status'] != 1) {
                    return array('error_code' => true, 'msg' => L_('群已经删除'));
                }

                // 加群是否审核
                if($community_info['is_check'] != 1) {
                    $add_status = 2;
                }else{
                    $add_status = 3;
                }

                // 跟新加群状态
                $update_data = array(
                    'add_status' => $add_status,
                    'order_id' => $order_id,
                );
                $upSql = D('Community_join')->where(array('add_id'=>$add_id))->data($update_data)->save();
                if ($upSql) {
                    if ($add_status==3) { // 不需审核 直接加入成功

                        // 加群成功添加成员数
                        $num = D('Community_info')->where(array('community_id' => $community_info['community_id']))->setInc('member_number');

                        // 如果是别人邀请加入，记录邀请人邀请数
                        if (!empty($join_info['add_source'])) {
                            $num2 = D('Community_join')->where(array('community_id' => $join_info['community_id'], 'add_uid' => $join_info['add_source']))->setInc('invitation_num');
                        }
                        //获取用户信息
                        $user_info = D('User')->get_user($join_info['add_uid']);
                        // 群主余额增加
                        // 收取平台费用
                        $money = $join_info['charge_money'];
                        $add_result = D('User')->add_money($community_info['group_owner_uid'], $money, L_("X1加入群【X2】，增加余额",array("X1" => $user_info['nickname'],"X2" => $community_info['community_name']))); 
                        if (C('config.community_join_get_merchant_percent')>0) {
                            $money = $money * C('config.community_join_get_merchant_percent') * 0.01; 
                            $user_result = D('User')->user_money($community_info['group_owner_uid'], $money, L_("X1加入群【X2】，平台抽成，减少余额",array("X1" => $user_info['nickname'],"X2" => $community_info['community_name']))); 
                        }
                        if (!$add_result['error_code']) {
                            // 群头像 群主未上传群聊头像
                            if (!$community_info['community_avatar']) {
                                D('Community_info')->change_group_avatar($join_info['community_id'], $join_info['add_uid']);
                            }
                            return array('error_code' => false, 'msg' => L_('加群成功'));
                        } else {
                            return array('error_code' => true, 'msg' => L_('加群失败'));
                        }
                    } else {
                        // 需要审核，通知群主
                        $database_user = D('User');
                        $database_community_user_formid = D('Community_user_formid');
                        $send_user = $database_user->field('group_openid')->where(array('uid' => $community_info['group_owner_uid']))->find();
                        $from_info = $database_community_user_formid->get($community_info['group_owner_uid']);
                        if ($from_info && $send_user && $send_user['group_openid']) {
                            // 审核中 推动模板消息 给群主
                            $apply_user = $database_user->field('nickname')->where(array('uid' => $join_info['add_uid']))->find();
                            $model = new templateWxapp(C('config.pay_wxapp_group_appid'), C('config.pay_wxapp_group_appsecret'));
                            $comm_nickname = $join_info['comm_nickname'] ? $join_info['comm_nickname'] : $apply_user['nickname'];
                            $data = array(
                                'touser' => $send_user['group_openid'],
                                'page' => 'pages/group/joinDetail/joinDetail?add_id=' . $add_id,
                                'form_id' => $from_info['formid'],
                                'keyword1' => $comm_nickname, // 姓名
                                'keyword2' => L_("申请加入群【X1】",array("X1" => $community_info['community_name'])), // 申请项目 
                                'keyword3' => L_('待审核'), // 状态
                                'keyword4' => date('Y-m-d H:i:s'), // 日期
                                'id' => $from_info['id'], // fromid主键id
                            );
                            $info = $model->sendWxappTempMsg('AT0052', $data);
                            if ($info) {
                                $database_community_user_formid->del($from_info['fromid']);
                            }
                        }

                        return array('error_code' => false, 'msg' => L_('加群成功'));
                    }
                } else {
                    return array('error_code' => true, 'msg' => L_('加群失败'));
                }
        }
    }

    
}
?>