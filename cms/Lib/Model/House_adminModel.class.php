<?php
class House_adminModel extends Model{
	/*角色列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false){
		if(!$column['village_id']){
			return null;
		}
    	$order = '`a`.`id` DESC';
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
		$condition_field = "`a`.*,`g`.`name`,`g`.`group_menus`";
    	$condition_table  = array(C('DB_PREFIX').'house_admin'=>'a');
    	$condition_where = "`a`.`village_id` =".$column['village_id'];
    	if(isset($column['wid'])){
            $condition_where .= " and `a`.`wid` =".$column['wid'];
        }
    	$join = ' LEFT JOIN '.C('DB_PREFIX').'house_admin_group as g on `a`.`group_id` = `g`.`group_id`';


    	$count_order = D('')->table($condition_table)->where($condition_where)->count();
    	$p = new Page($count_order,$pageSize,'page');
    	$list = D('')->field($condition_field)->table($condition_table)->join($join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

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

	/**
	 * [get_info_one 获取单个信息]
	 * @return [type] [description]
	 */
	public function get_info_one($where=array()){
		$info = $this->where($where)->find();
        if($info && !empty($info['wid'])){
            $info['menus'] =$this->getProgrammeGroupMenus($info['village_id'],$info['wid'],$info['group_id'],$info['menus']);
        }else{
            if($info['group_id']){
                $group = D('House_admin_group')->where(array('group_id'=>$info['group_id'],'status'=>1))->find();
                $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
            }
        }
//		if ($info['group_id']) { // 绑定了权限分组
//			$group = D('House_admin_group')->where(array('group_id'=>$info['group_id'],'status'=>1))->find();
//			$info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
//		}
		if ($info['menus']) {
			$menus = explode(',', $info['menus']);
			$menus_info = D('House_menu_new')->where(array('status'=>1,'id'=>array('in',$menus)))->select();
			$exist_menus = array();
			if ($menus_info) {
				foreach ($menus_info as $key => $value) {
					$exist_menus[] = $value['id'];
				}
			}
			$info['menus'] = implode(',',$exist_menus);
		}
		return $info;
	}


    //todo 获取工作人员下所有分组id
	public function getProgrammeGroupId($village_id,$wid,$group_id=0){
        $groupID=[];
        if($group_id > 0){
            $groupID[]=$group_id;
        }
        $list = D('House_programme_relation')
            ->join('as r left join '.C('DB_PREFIX').'house_programme as p ON p.id = r.pid')
            ->where([
                'r.wid'=>$wid,
                'r.village_id'=>$village_id
            ])->order('r.id desc')->field('p.group_id')->select();
        if($list){
            $list=array_column($list,'group_id');
            if(!empty($groupID)){
                $groupID=array_merge($list,$groupID);
            }else{
                $groupID=$list;
            }
        }
        return !empty($groupID) ? array_values(array_unique($groupID)) : [];
    }

    //二维数组转一维
    public function once_array($arr){
        $arr2=[];
        foreach ($arr as $k1=>$v1){
            if (is_array($v1)) {
                foreach ($v1 as $k2=>$v2){
                    $arr2[]=$v2;
                }
            }
        }
        return $arr2;
    }


    //todo 查询工作人员下所有menus
    public function getProgrammeGroupMenus($village_id,$wid,$group_id=0,$menus){
        $list=$this->getProgrammeGroupId($village_id,$wid,$group_id);
        if(empty($list)){
            return $menus;
        }
        $data = D('House_admin_group')->where(array(
            'group_id'=>array('in',$list),
            'status'=>1
        ))->getField('group_menus',true);
        if(empty($data)){
            return $menus;
        }
        foreach ($data as &$v){
            if($v){
                $v=explode(',',$v);
            }
        }
        unset($v);
        $data=$this->once_array($data);
        if(empty($data)){
            return $menus;
        }
        $data=array_unique($data);
        if($menus){
            $data=array_unique(array_merge($data,explode(',',$menus)));
        }
        return implode(',',$data);
    }


}