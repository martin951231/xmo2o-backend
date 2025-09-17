<?php
class Property_adminModel extends Model{
	/*角色列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false){
		if(!$column['property_id']){
			return null;
		}
    	$order = '`id` DESC';
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
    	$condition_where = "`property_id` =".$column['property_id'];
        $count_order = D('Property_admin')->where($condition_where)->count();
    	$p = new Page($count_order,$pageSize,'page');
        $list = D('Property_admin')->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
    	$return['pagebar'] = $p->show();
    	$return['list'] = $list;
    	return $return;
	}

	public function get_one($id){
		if(!$id){
			return false;
		}
		$info = $this->where(array('id'=>$id))->find();
		if ($info['group_id']) { // 绑定了权限分组
			$group = D('House_admin_group')->where(array('group_id'=>$info['group_id'],'status'=>1))->find();
			$info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
		}
		return $info;
	}


}