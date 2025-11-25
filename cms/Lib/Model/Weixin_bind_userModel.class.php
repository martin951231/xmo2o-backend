<?php
class Weixin_bind_userModel extends Model{
	/*得到所有用户*/
	public function get_info($mer_id,$field,$value){
		$condition_weixin_bind_user['mer_id'] = $mer_id;
		$condition_weixin_bind_user[$field] = $value;
		$now_user = $this->field(true)->where($condition_weixin_bind_user)->order('`id` DESC')->find();
		return $now_user;
	}
	
	/*得到社区的所有用户*/
	public function get_village_info($property_id,$field,$value){
		$condition_weixin_bind_user['property_id'] = $property_id;
		$condition_weixin_bind_user[$field] = $value;
		$now_user = $this->field(true)->where($condition_weixin_bind_user)->order('`id` DESC')->find();
		return $now_user;
	}
}