<?php
class House_admin_groupModel extends Model{
	/*角色列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false){
		if(!$column['village_id']){
			return null;
		}
    	$order = '`group_id` DESC';
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
    	$count_order = $this->where($column)->count();
    	if ($pageSize) {
	    	$p = new Page($count_order,$pageSize,'page');
	    	$list = $this->where($column)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
    		$return['pagebar'] = $p->show();
    	}else{
	    	$list = $this->where($column)->order($order)->select();
    	}
    	$return['list'] = $list;
    	return $return;
	}

	public function get_one($id){
		if(!$id){
			return false;
		}
		$info = $this->where(array('group_id'=>$id))->find();
		return $info;
	}

	/**
	 * [get_info_one 获取单个信息]
	 * @return [type] [description]
	 */
	public function get_info_one($where=array()){
		$info = $this->where($where)->find();
		return $info;
		
	}
}