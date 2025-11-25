<?php
/*
 *  计划任务 活动快开始时发个模板消息给报名者，提前发消息时间或者不发由后台配置想决定
 * */
class plan_platform_activity extends plan_base{
    public function runTask(){
        $agreement_model = M('Activity_config');
        $platform_activity_notice_switch = $agreement_model->where(array('alias_info' => 'platform_activity_notice_switch'))->getField('content');
        $platform_activity_notice_time = $agreement_model->where(array('alias_info' => 'platform_activity_notice_time'))->getField('content');
        if ($platform_activity_notice_switch == 1 && $platform_activity_notice_time > 0) {
            // 获取没有推送的结束时间小于当前时间的投票信息
            $now_time = time();
            $notice = $now_time + intval($platform_activity_notice_time) * 3600 + 1;
            $where = array(
                'a.status' => 1,
                'b.status' => 1,
                'a.notice_time' => array('elt', 0),
                'b.is_del' => 0,
                'b.start_time' => array('lt',$notice)
            );
            $m_activity_join = M('Activity_join');
            $activity_join = $m_activity_join->alias('a')
                ->join('LEFT JOIN pigcms_activity as b on a.activity_id=b.activity_id')
                ->field('a.*,b.uid as activity_uid, b.title, b.start_time')
                ->distinct(true)
                ->where($where)
                ->order('a.add_time asc')
                ->select();
            if ($activity_join) {
                // 推送给参与活动的活动开始信息
                $m_user = M('User');
                foreach ($activity_join as $val) {
                    $this->keepThread();
                    $user_info = $m_user->field(true)->where(array('uid'=>$val['uid']))->find();
                    if ($user_info['openid']) {
                        $m_activity_join->where(array('join_id' => $val['join_id']))->data(array('notice_time' => time()))->save();
                        $start_time_info = date('Y-m-d H:i:s', $val['start_time']);
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Platform&a=indexDetail&activity_id=' . $val['activity_id'];
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $user_info['openid'],
                            'first' => '活动【'.$val['title'].'】将于'.$start_time_info.'开始',
                            'OrderSn' => $val['activity_id'],
                            'OrderStatus' => '活动即将开始',
                            'remark' => date('Y-m-d H:i:s')
                        ));
                    }
                }
            }
        }
        return true;
    }
}
?>