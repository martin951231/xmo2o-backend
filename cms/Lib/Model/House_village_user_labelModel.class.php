<?php
class House_village_user_labelModel extends Model
{
	// 政治面貌
	public $user_political_affiliation_title = '政治面貌';
	public $user_political_affiliation_arr = array(
		1 => '党员',
		2 => '共青团员',
		3 => '群众',
	);
	// 特殊人群
	public $user_special_groups_title = '特殊人群';
	public $user_special_groups_arr = array(
		1 => '刑满释放',
		2 => '社区矫正',
		3 => '重点青少年',
		4 => '吸毒',
		5 => '艾滋病',
		6 => '精神病',
	);
	// 重点人群
	public $user_focus_groups_title = '重点人群';
	public $user_focus_groups_arr = array(
		1 => '服刑人员',
		2 => '非法组织',
		3 => '上访人员'
	);
	// 弱势困难群体
	public $user_vulnerable_groups_title = '关怀群体';
	public $user_vulnerable_groups_arr = array(
		1 => '低保',
		2 => '残疾人',
		3 => '空巢',
		4 => '失独老人',
		5 => '孤寡老人',
        6 => '退役军人',
        7 => '留守儿童',
	);

	/**
	 * @param $value
	 * @param string $field
	 * @return mixed
	 */
	public function getInfo($value, $field='bind_id') {
		$where = array();
		$where[$field] = $value;
		$info = $this->where($where)->find();
		if ($info && $info['user_special_groups']) {
			$info['user_special_groups'] = explode(',',$info['user_special_groups']);
		}
		if ($info && $info['user_focus_groups']) {
			$info['user_focus_groups'] = explode(',',$info['user_focus_groups']);
		}
		if ($info && $info['user_vulnerable_groups']) {
			$info['user_vulnerable_groups'] = explode(',',$info['user_vulnerable_groups']);
		}
		return $info;
	}

	//todo 组装标签数据
	public function getUserLabelMethod($street_id,$village_id,$uid=0,$pigcms_id=0){
	    $Street_party_bind_user=M('Street_party_bind_user');
        $Area_street_party_branch=M('Area_street_party_branch');
        $party_status5=0;
        $user_label_arr=[];
        $user_party_arr=[];
        $user_custom_label_arr=[];
        // 政治面貌
        $user_political_affiliation_title = $this->user_political_affiliation_title;
        $user_political_affiliation_arr = $this->user_political_affiliation_arr;
        // 特殊人群
        $user_special_groups_title = $this->user_special_groups_title;
        $user_special_groups_arr = $this->user_special_groups_arr;
        // 重点人群
        $user_focus_groups_title = $this->user_focus_groups_title;
        $user_focus_groups_arr = $this->user_focus_groups_arr;
        // 弱势困难群体
        $user_vulnerable_groups_title = $this->user_vulnerable_groups_title;
        $user_vulnerable_groups_arr = $this->user_vulnerable_groups_arr;
        //用户数据
        if($pigcms_id){
            $user_label_arr = $this->getInfo($pigcms_id,'bind_id');
        }
        //用户uid查询关联对应社区
        if($uid){
            $party_bind_user =$Street_party_bind_user->where(['uid'=>$uid,'party_status' => ['in', [1,4,5]]])->field('party_id,party_status')->find();
            $user_label_arr['user_party_id']=$party_bind_user['party_id'] ? $party_bind_user['party_id'] : 0;
            if($party_bind_user['party_status'] == 5){
                $party_bind_user=[];
                $party_bind_user['party_id']=0;
                $party_status5=5;
            }
        }
        if($party_status5 == 5 && $user_label_arr && $user_label_arr['user_political_affiliation'] == 1){
            $user_label_arr['user_political_affiliation']=0;
        }
        //街道党支部数据
        if($street_id){
            $party_where=[];
            $party_where['status'] = ['neq','-1'];
            $party_where['street_id'] = $street_id;
            $user_party_arr = $Area_street_party_branch->where($party_where)->field('id,name')->order('sort desc,id desc')->select();
        }
        //获取小区自定义标签
        $house_custom_label_arr=$this->getVillageCustomLabel($village_id,'userLabel');
        if($pigcms_id){
            $user_custom_label_arr=$this->getVillageUserCustomLabel($pigcms_id);
        }
        $data=[
            'user_political_affiliation_title'=>$user_political_affiliation_title,
            'user_political_affiliation_arr'=>$user_political_affiliation_arr,
            'user_special_groups_title'=>$user_special_groups_title,
            'user_special_groups_arr'=>$user_special_groups_arr,
            'user_focus_groups_title'=>$user_focus_groups_title,
            'user_focus_groups_arr'=>$user_focus_groups_arr,
            'user_vulnerable_groups_title'=>$user_vulnerable_groups_title,
            'user_vulnerable_groups_arr'=>$user_vulnerable_groups_arr,
            'user_label_arr'=>$user_label_arr,
            'user_party_arr'=>$user_party_arr,
            'house_custom_label_arr'=>$house_custom_label_arr,
            'user_custom_label_arr'=>$user_custom_label_arr
        ];
        return $data;
    }

    //todo 修改标签数据
    public function saveUserLabelMethod($param){
	    $time=time();
	    $House_village_user_label=M('House_village_user_label');
	    $Street_party_bind_user=M('Street_party_bind_user');
        $user_label = array();
        if (!empty($param['user_political_affiliation'])) {
            $user_label['user_political_affiliation'] = $param['user_political_affiliation'];
        }
        if (!empty($param['user_special_groups'])) {
            $user_label['user_special_groups'] = implode(',', $param['user_special_groups']);
        }else{
            $user_label['user_special_groups']='';
        }
        if (!empty($param['user_focus_groups'])) {
            $user_label['user_focus_groups'] = implode(',', $param['user_focus_groups']);
        }else{
            $user_label['user_focus_groups']='';
        }
        if (!empty($param['user_vulnerable_groups'])) {
            $user_label['user_vulnerable_groups'] = implode(',', $param['user_vulnerable_groups']);
        }else{
            $user_label['user_vulnerable_groups']='';
        }
        if(isset($param['party_type'])){
            $user_label['party_type'] = $param['party_type'];
        }
        if(!empty($param['pigcms_id'])){
            $user_label_id = $House_village_user_label->where(array('bind_id'=>$param['pigcms_id']))->getField('id');
            if ($user_label_id) {
                $user_label['last_time'] = $time;
                $House_village_user_label->where(array('id'=>$user_label_id))->data($user_label)->save();
            }  else {
                $user_label['bind_id'] = $param['pigcms_id'];
                $user_label['add_time'] = $time;
                $House_village_user_label->data($user_label)->add();
            }
        }
        if($param['party_type'] > 0 && !empty($param['uid'])){
            $party_bind_user = $Street_party_bind_user->where(['uid'=>$param['uid']])->field('id,party_id')->find();
            if(!$party_bind_user)
            {
                $party_data = [
                    'uid'=>$param['uid'],
                    'party_id'=>$param['user_party_id'],
                    'create_time'=>$time
                ];
                $Street_party_bind_user->data($party_data)->add();
            }else {
                if ($party_bind_user['party_id'] != $param['user_party_id']) {
                    $Street_party_bind_user->where(['id' => $party_bind_user['id']])->data(['party_id' => $param['user_party_id']])->save();
                }
            }
        }
        if(!empty($param['pigcms_id']) && isset($param['user_label_groups'])){
            $this->sysSaveVillageUserCustomLabel($param['pigcms_id'],$param['user_label_groups']);
        }
        return true;
    }

    //todo 获取小区自定义标签数据
    public function getVillageCustomLabel($village_id,$cat_function){
	    $house_village_label_cat=M('House_village_label_cat');
        $house_village_label=M('House_village_label');
	    $list=[];
        $cat_list=$house_village_label_cat->where(['cat_function'=>$cat_function,'village_id'=>$village_id,'status'=>1])->field('cat_id as id,cat_name as name')->order('cat_id desc')->select();
        if(empty($cat_list)){
            return $list;
        }
        $cat_id = array_column($cat_list, 'id');
        $label_list=$house_village_label->where(['status'=>0,'cat_id'=>['in',$cat_id]])->field('id,cat_id as pid,label_name as name')->order('id desc')->select();
        foreach ($cat_list as $k1=>$v1){
            $data=[
                'id'=>$v1['id'],
                'name'=>$v1['name']
            ];
            foreach ($label_list as $k2=>$v2){
                if($v1['id'] == $v2['pid']){
                    $data['children'][]=$v2;
                }
            }
            $list[]=$data;
        }
        return $list;
    }

    //todo 获取用户自定义标签数据
    public function getVillageUserCustomLabel($pigcms_id){
        $house_village_user_label_bind=M('House_village_user_label_bind');
        $user_label=$house_village_user_label_bind->where(['bind_id'=>$pigcms_id])->getField('village_label_id',true);
        return $user_label;
    }

    //todo 同步修改用户自定义标签
    public function sysSaveVillageUserCustomLabel($pigcms_id,$data){
        $time=time();
	    $house_village_user_label_bind=M('House_village_user_label_bind');
	    $user_label=$house_village_user_label_bind->where(['bind_id'=>$pigcms_id])->field('id,village_label_id,bind_id')->select();
        $user_label_used_id=[];//已记录的标签id
        $user_label_del_id=[];//要删除的标签id
        $user_label_retain_id=[];//要保留的标签id
        $user_label_arr=[];
	    if($user_label){
            $user_label_used_id=array_column($user_label, 'village_label_id');
        }
        $user_label_new_id=D('House_admin')->once_array($data);
	    if(!$user_label_new_id && $user_label){
            $house_village_user_label_bind->where(['bind_id'=>$pigcms_id])->delete();
            return true;
        }
	    foreach ($user_label_used_id as $v){
	        if(!in_array($v,$user_label_new_id)){
                $user_label_del_id[]=$v;
            }else{
                $user_label_retain_id[]=$v;
            }
        }
	    if($user_label_del_id){//删除无用标签
            $house_village_user_label_bind->where(['bind_id'=>$pigcms_id,'village_label_id'=>['in',$user_label_del_id]])->delete();
        }
	    foreach ($user_label_new_id as $v){
	        if(!in_array($v,$user_label_retain_id)){
                $user_label_arr[]=[
                    'village_label_id'=>$v,
                    'bind_id'=>$pigcms_id,
                    'is_delete'=>0,
                    'create_at'=>$time,
                    'update_at'=>$time
                ];
            }
        }
	    if($user_label_arr){
            $house_village_user_label_bind->addAll($user_label_arr);
        }
	    return true;
    }



}
?>