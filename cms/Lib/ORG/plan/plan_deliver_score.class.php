<?php
/*配送员等级积分自动减少
 *20190321
 *htm
 * */
class plan_deliver_score extends plan_base{
	public function runTask(){
        if (C('config.open_deliver_level')&&C('config.deliver_level_down_days')&&C('config.deliver_level_down_num')&&C('config.deliver_level_down_score')) {
           // 查询符合条件的配送员
            $end_time = strtotime(date('Y-m-d'));
            $start_time = $end_time - floatval(C('config.deliver_level_down_days')*86400);
            $condition['s.end_time'] = array('between',array($start_time,$end_time));
            $condition['s.uid'] = array('gt',0);
            $condition['u.group'] = 1; // 系统配送员
            $deliver_list = D('Deliver_supply')->field('s.uid,count(*) as count')->join(' as s left join '.C('DB_PREFIX').'deliver_user as u on s.uid=u.uid')->where($condition)->group('s.uid')->select();
            $this->keepThread();
            if ($deliver_list) {
                foreach ($deliver_list as $value) {
                    $this->keepThread();
                    if ($value['uid']&&$value['count']<C('config.deliver_level_down_num')) {
                        $score = C('config.deliver_level_down_score');
                        $desc = C('config.deliver_level_down_days').'天以内，未完成'.C('config.deliver_level_down_num').'单，扣除'.$score.'积分';
                        $res = D('Deliver_user')->deduct_score($value['uid'],$score,$desc,7);
                    }
                }
            }
        }
		return true;
	}
}
?>