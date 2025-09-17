<?php
/*
 *  计划任务 线下活动未成团自动取消活动并退款
 * */
class activity_offline_cancel extends plan_base{
    public function runTask(){
        $database_activity_offline = M('Activity_offline');
        $where = 'status=1 AND is_del=0 AND deadline<'.time().' AND number<open_number';
        $activity_offline = $database_activity_offline->where($where)->select();
        if ($activity_offline) {
            foreach ($activity_offline as $key => $value) {
                $this->cancle_activity($value);
            }
        }
        return true;
    }

    public function cancle_activity($activity)
    {
        // 处理添加的数据
        $database_activity_offline = M('Activity_offline');
        $database_activity_offline_join= M('Activity_offline_join');
        $database_activity_offline_order= M('Activity_offline_order');
        
        // 修改活动状态
        $res = $database_activity_offline->where('activity_id='.$activity['activity_id'])->data(array('status'=>2))->save();
        if ($res){
            return array('error'=>1,'msg'=>'取消失败，请重试');
        }

        //退还报名费
        if ($activity['fees'] >0 || $activity['fees_all'] >0) {
            // 查询有效订单
            $where['a.activity_id'] = $activity['activity_id'];
            $where['a.is_del'] = 0;
            $where['a.status'] = 1;
            $where['b.status'] = array('in', array(1,5));
            $order_list = $database_activity_offline_order
            ->alias('a')
            ->field('a.*,b.join_id')
            ->distinct(true)
            ->where($where)
            ->join('join '.C('DB_PREFIX').'activity_offline_join as b on a.activity_id=b.activity_id')
            ->select();
            if ($order_list) {
                foreach ($order_list as $key => $value) {
                    $return = $this->refund($value,$activity);
                }
            }
            return array('error'=>0,'msg'=>'取消成功');
        } else {
            // 免费活动也对用户进行通知
            $activity_join = M('Activity_offline_join')
                ->alias('a')
                ->join('join '.C('DB_PREFIX').'user as b on a.uid=b.uid')
                ->field('a.join_id, a.uid, a.activity_id, b.openid')
                ->distinct(true)
                ->where(array('a.activity_id' => $activity['activity_id'], 'a.status' => array('in', array(1,5))))->select();

            if ($activity_join) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                foreach ($activity_join as $val) {
                    // 处理成已取消状态
                    if ($val['join_id']) {
                        $database_activity_offline_join->where(array('join_id'=>$val['join_id']))->data(array('status'=>6))->save();
                    }

                    //活动退款模板推送
                    if ($val['openid']) {
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityOrder&join_id='.$val['join_id'].'&activity_id=' . $activity['activity_id'];
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $val['openid'],
                            'first' => '活动【'.$activity['title'].'】拼团失败，已经取消',
                            'OrderSn' => $val['activity_id'],
                            'OrderStatus' => '已取消',
                            'remark' => date('Y-m-d H:i:s')
                        ));
                    }
                }
            }
            return array('error'=>0,'msg'=>'取消成功');
        }
    }
   
    public function refund($now_order,$activity){
        $activity_offline_order = M('Activity_offline_order');
        $activity_offline_join = D('Activity_offline_join');
        $plat_order = D('Plat_order');

        $order_id =  $now_order['order_id'];
        $join_id =  $now_order['join_id'];

        if($now_order['status'] ==3 ){
            return array('error'=>1,'msg'=>'该订单已经退款!');
        } elseif($now_order['status'] == 2 ){
            return array('error'=>1,'msg'=>'该订单未支付');
        }elseif($now_order['status'] == 0 ){
            return array('error'=>1,'msg'=>'该订单已失效');
        }

        // 订单状态更新
        if(!$activity_offline_order->where(array('order_id'=>$order_id))->data(array('status'=>3))->save()){
            return array('error'=>1,'msg'=>'修改订单状态失败');
        }

        // 报名状态更新
        $join_info = $activity_offline_join->where(array('join_id'=>$join_id))->find();
        if ($join_info['status'] != 4) {
            if(!$activity_offline_join->where(array('join_id'=>$join_id))->data(array('status'=>4))->save()){
                return array('error'=>1,'msg'=>'修改报名状态失败');
            }
        }

        // 退款
        $refund  = $plat_order->order_refund(array('business_type'=>'activity_offline','business_id'=>$now_order['order_id']));
        if(!$refund['error']){
            return array('error'=>1,'msg'=>$refund['msg']);
        }

        //活动退款模板推送
        $user_info = M('User')->field(true)->where(array('uid'=>$now_order['uid']))->find();
        if ($user_info['openid']) {
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityOrder&join_id='.$join_id.'&activity_id=' . $activity['activity_id'];
            $model->sendTempMsg('TM00017', array(
                'href' => $href,
                'wecha_id' => $user_info['openid'],
                'first' => '活动【'.$activity['title'].'】拼团失败，已经取消',
                'OrderSn' => $order_id,
                'OrderStatus' => '已退款',
                'remark' => date('Y-m-d H:i:s')
            ));
        }
        return array('error'=>0,'msg'=>'退款成功');
    }
}
?>