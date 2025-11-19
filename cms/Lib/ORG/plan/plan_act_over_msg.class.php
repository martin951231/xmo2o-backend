<?php
/*推送拼团/砍价即将结束的信息*/
class plan_act_over_msg extends plan_base{
	public function runTask(){
		$group_list=D('Mall_group_team')->get_fast_over_order();
		$cut_list=D('Mall_bargain_team')->get_fast_over_order();
		
		if(empty($group_list) && empty($cut_list)){
			return true;
		}
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		foreach ($group_list as $k => $v) {
			$name=mb_substr($v['name'], 0,8,'utf-8');
			$first='拼团进度通知，您的【'.$name.'】拼团还差'.$v['need_num'].'人成团，剩余时间1小时，赶紧拉人来拼团吧！';
			$href = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&goods_id='.$v['goods_id'].'&group_id='.$v['group_id'].'&team_id='.$v['team_id'];
			$model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $v['openid'],
                    'first' => $first,
                    'OrderSn' => $v['real_orderid'],
                    'OrderStatus' => '拼团中',
                    'remark' => date('Y-m-d H:i:s')
                ), $v['mer_id']);
		}
		foreach ($cut_list as $k => $v) {
			$name=mb_substr($v['name'], 0,8,'utf-8');
			$first='砍价进度通知，您的【'.$name.'】砍价还差'.$v['need_price'].'元，剩余时间1小时，赶紧拉人来一起砍价吧！';
			$href = C('config.site_url').'/wap.php?c=Mall&a=detail&goods_id='.$v['goods_id'].'&bar_id='.$v['bar_id'].'&team_id='.$v['team_id'];
			$model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $v['openid'],
                    'first' => $first,
                    'OrderSn' => $v['real_orderid'],
                    'OrderStatus' => '砍价中',
                    'remark' => date('Y-m-d H:i:s')
                ), $v['mer_id']);
		}
		return true;
	}
}
?>