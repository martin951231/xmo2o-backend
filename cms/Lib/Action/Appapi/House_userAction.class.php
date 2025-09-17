<?php
/*
 * 社区业主
 *
 */
class House_userAction extends BaseAction{
	//	获取业主的全部列表
    public function index(){
    	$village_id	=	I('village_id');
    	$this->is_existence();
		$page	=	I('page',1);
		$page_coun	=	I('page_coun',10);
		$condition_where['village_id'] = $village_id;
		$condition_where['parent_id'] = 0;
		$arr = $this->get_limit_list_page($condition_where,$page,$page_coun);
		if(empty($arr)){
			$arr	=	(object)array();
		}
		$this->returnCode(0,$arr);
	}
	//	获取单个业主信息
    public function owner_one(){
    	$pigcms_id	=	I('pigcms_id');
    	if(empty($pigcms_id)){
			$this->returnCode('20090009');
		}
    	$this->is_existence();
		$arr = M('House_village_user_bind')->field(array('parent_id'),true)->where(array('pigcms_id'=>$pigcms_id))->find();
		if(empty($arr)){
			$this->returnCode('20090010');
		}
		$this->returnCode(0,$arr);
	}
	//	修改业主信息
	public	function	eidt(){
		$pigcms_id	=	I('pigcms_id');
		if(empty($pigcms_id)){
			$this->returnCode('20090009');
		}
		$this->is_existence();
		$arr	=	array(
			'name'			=>	I('name'),				//业主名称
			'phone'			=>	I('phone'),				//业主联系方式
			'housesize'		=>	I('housesize'),			//房子的平方，计算物业费使用
			'water_price'	=>	I('water_price'),		//水费总欠费
			'electric_price'=>	I('electric_price'),	//电费总欠费
			'gas_price'		=>	I('gas_price'),			//燃气费总欠费
			'park_price'	=>	I('park_price'),		//停车费总欠费
			'property_price'=>	I('property_price'),	//物业费总欠费
			'park_flag'		=>	I('park_flag'),			//是否有停车位 1有 0没有
			'address'		=>	I('address'),			//住址
		);
		foreach($arr as $k=>$v){
			if($v == null){
				unset($arr[$k]);
			}
    	}
    	$aVillage_id	=	M('House_village_user_bind')->field(array('village_id'))->where(array('pigcms_id'=>$pigcms_id))->find();
    	if($aVillage_id){
			$aSave	=	M('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->data($arr)->save();
    	}else{
			$this->returnCode('20090010');
    	}
    	if($aSave){
			$this->returnCode(0);
    	}else if($aSave === 0){
			$this->returnCode('20090007');
    	}else{
			$this->returnCode('20090011');
    	}
	}
	//	搜索业主
	public	function	owner_search(){
		$this->is_existence();
		$search	=	I('search');
		if(empty($search)){
			$this->returnCode('20090012');
		}
		$village_id	=	I('village_id');
		$page	=	I('page',1);
		$page_coun	=	I('page_coun',10);
		$where['name'] = array('like',array('%'.$search.'%','%.com'));
		$where['phone'] = array('like',array('%'.$search.'%','%.com'));
		$where['address'] = array('like',array('%'.$search.'%','%.com'));
		$where['_logic'] = 'OR';
		$condition_where['village_id'] = array('eq',$village_id);
		$condition_where['_complex'] = $where;
		$arr = $this->get_limit_list_page($condition_where,$page,$page_coun);
		if(empty($arr)){
			$this->returnCode('20090013');
		}
		$this->returnCode(0,$arr);
	}
	//	得到小区下所有的业主列表
	public function get_limit_list_page($condition_where,$page=1,$page_coun=10){
		$return = array();
		$count_user = D('House_village_user_bind')->where($condition_where)->count();
		$user_list = D('House_village_user_bind')->field(array('parent_id'),true)->where($condition_where)->order('`pigcms_id` DESC')->page($page,$page_coun)->select();
		if($user_list){
			$return['totalPage']	= ceil($count_user/$page_coun);
			$return['page']			= intval($page);
			$return['user_count']	= count($user_list);
			$return['user_list']	= $user_list;
		}else{
			return false;
		}
		return $return;
	}
	//	新增业主
	public	function	add(){
		$this->is_existence();
		$arr	=	array(
			'village_id'	=>	I('village_id'),
			'usernum'		=>	I('usernum'),
			'name'			=>	I('name'),
			'phone'			=>	I('phone'),
			'housesize'		=>	I('housesize'),
			'water_price'	=>	I('water_price',0),
			'electric_price'=>	I('electric_price',0),
			'gas_price'		=>	I('gas_price',0),
			'park_price'	=>	I('park_price',0),
			'property_price'=>	I('property_price',0),
			'park_flag'		=>	I('park_flag',0),
			'address'		=>	I('address'),
		);
		if(empty($arr['usernum'])){
			$this->returnCode('20090057');
		}
		if(empty($arr['name'])){
			$this->returnCode('20090059');
		}
		if(empty($arr['phone'])){
			$this->returnCode('20090060');
		}
		if(empty($arr['housesize'])){
			$this->returnCode('20090061');
		}
		if(empty($arr['address'])){
			$this->returnCode('20090062');
		}
		$aFind	=	M('House_village_user_bind')->field(array('pigcms_id'))->where(array('village_id'=>$arr['village_id'],'usernum'=>$arr['usernum']))->find();
		if($aFind){
			$this->returnCode('20090058');
		}
		$add	=	M('House_village_user_bind')->data($arr)->add();
		if($add){
			$this->returnCode(0);
		}else{
			$this->returnCode('20090063');
		}
	}
}