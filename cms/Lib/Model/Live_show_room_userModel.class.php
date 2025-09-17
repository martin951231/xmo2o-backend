<?php
class Live_show_room_userModel extends Model
{
	//获取房间里的人数  online=在线人数 viewer=观看人数(来一个就算+1)
	public function get_user_num($live_id, $type = 'online'){
		if(empty($live_id)) return 0;
		if($type == 'online'){
			$where['status'] = 1;
		}
		$where['live_show_id'] = $live_id;
		return $this->where($where)->count();
	}
}