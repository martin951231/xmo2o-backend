<?php
/*
 * 社区2.0业主
 *
 */
class H_userAction extends BaseAction{
	//	获取业主的全部列表
    public function index(){
    	$this->is_existence();
    	//验证权限
    	if(!in_array(91, $this->power)){    		
			$this->returnCode('20090103');
    	}

    	$village_id	=	I('village_id');
		$page	=	I('page',1);
		$page_coun	=	I('page_coun',10);
		$condition_where['village_id'] = $village_id;
		$condition_where['parent_id'] = 0;
		$condition_where['type'] = array('neq',4);

		$search	=	I('search');
		if($search){
			$where['name'] = array('like',array('%'.$search.'%','%.com'));
			$where['phone'] = array('like',array('%'.$search.'%','%.com'));
			$where['address'] = array('like',array('%'.$search.'%','%.com'));
			$where['_logic'] = 'OR';
			$condition_where['_complex'] = $where;
		}

		$arr = $this->get_limit_list_page($condition_where,$page,$page_coun);
		if(empty($arr)){
			$arr = (object)array();
		}else{
			foreach ($arr['user_list'] as $key => $value) {
	        	// 家属个数
	        	$child = D('House_village_user_bind')->where(array('parent_id'=>$value['pigcms_id'],'village_id'=>$village_id))->count();
	        	$arr['user_list'][$key]['child_count'] = $child ? $child : 0;
			}

		}

		$this->returnCode(0,$arr);
	}
	//	获取单个业主信息
    public function owner_one(){
    	$this->is_existence();
    	//验证权限
    	if(!in_array(91, $this->power) && !in_array(67, $this->power) ){    		
			$this->returnCode('20090103');
    	}

    	$pigcms_id	=	I('pigcms_id');
    	$village_id	=	I('village_id');
    	if(empty($pigcms_id)){
			$this->returnCode('20090009');
		}

		$info = M('House_village_user_bind')->field(array('parent_id'),true)->where(array('pigcms_id'=>$pigcms_id))->find();
		if(empty($info)){
			$this->returnCode('20090010');
		}

		$database_house_village_floor = D('House_village_floor');
        if($info['floor_id']){
            $floor_info = $database_house_village_floor->where(array('floor_id'=>$info['floor_id']))->find();
            $info['floor_info'] = $floor_info;
        }


        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $vacancy_where['status'] = array('in' , '1,2,3');
        $vacancy_where['village_id'] = $village_id;
        $vacancy_where['usernum'] = $info['usernum'];
        $vacancy_where['is_del'] = 0;
        $result = $database_house_village_user_vacancy->where($vacancy_where)->find();
        $info['room_info'] = $result;

        //车位信息
        $position_list = D('House_village_bind_position')->get_user_position_bind_list(array('pigcms_id'=>$pigcms_id,'village_id'=>$village_id));
        //车辆信息
        $car_list = D('House_village_bind_car')->get_user_car_bind_list(array('pigcms_id'=>$pigcms_id));
        $info['position_list'] = $position_list['list'];
        $info['car_list'] = $car_list;

         // 自定义缴费项
        $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb', C('DB_PREFIX').'house_village_payment_standard'=>'ps', C('DB_PREFIX').'house_village_payment'=>'p'))->where("psb.pigcms_id= '".$pigcms_id."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
        $payment_list = $payment_list ? $payment_list : array();

        // 自定义缴费项（车位）
        $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$pigcms_id));
        $payment_list = array_merge($payment_list, $position_payment_list);
    	$cycle_type = array(
                    'Y'=>L_('年'),
                    'M'=>L_('月'),
                    'D'=>L_('日'),
                );

    	$cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
        foreach ($payment_list as $kk => $vv) {
            $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);

            // 计算结束时间
            $vv['end_time'] = strtotime(date('Y-m-d',$vv['start_time']).'+'.$vv['cycle_sum']*$vv['pay_cycle'].$cycle_type[$vv['cycle_type']]);
            $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
            $payment_list[$kk]['cycle_type'] = $cycle_type[$vv['cycle_type']];
            if ($vv['garage_num']) {
                $payment_list[$kk]['payment_name'] = $vv['payment_name'].'('.$vv['garage_num'].'-'.$vv['position_num'].')';
            }
        }

        $info['payment_list'] = $payment_list;
		$this->returnCode(0,$info);
	}
	//	修改业主信息
	public function eidt(){
		$this->is_existence();
    	//验证权限
    	if(!in_array(93, $this->power)){    		
			$this->returnCode('20090103');
    	}

		$pigcms_id	=	I('pigcms_id');
		if(empty($pigcms_id)){
			$this->returnCode('20090009');
		}
		$village_id = I('village_id');

		$arr = array(
			'name'			=>	I('name'), //姓名
			'phone'			=>	I('phone'),//手机号
			// 'water_price'	=>	I('water_price',0),//水费总欠费
			// 'electric_price'=>	I('electric_price',0),//电费总欠费
			// 'gas_price'		=>	I('gas_price',0),//燃气费总欠费
			// 'park_price'	=>	I('park_price',0),//停车费总欠费
			// 'property_price'=>	I('property_price',0),//物业费总欠费
			// 'park_flag'		=>	I('park_flag',0), //是否有停车位 1有 0没有
		);

		if(empty($arr['name'])){
			$this->returnCode('20090059');
		}
		if(empty($arr['phone'])){
			$this->returnCode('20090060');
		}


		// 用户id
		$now_user = D('User')->get_user(I('phone'), 'phone');
        if ($now_user) {
            $arr['uid'] = $now_user['uid'];
        }else{
			$arr['uid'] = 0;
		}

		// foreach($arr as $k=>$v){
		// 	if($v == null){
		// 		unset($arr[$k]);
		// 	}
  		//   	}

    	$bind_info = M('House_village_user_bind')->field(true)->where(array('pigcms_id'=>$pigcms_id,'village_id'=>$village_id))->find();
    	if($bind_info){
			$aSave = M('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id,'village_id'=>$village_id))->data($arr)->save();
    	}else{
			$this->returnCode('20090010');
    	}
    	if($aSave){
    		//修改房间表 信息
			$condition_vacancy['usernum']    = $bind_info['usernum'];
			$condition_vacancy['village_id'] = $village_id;
			
			$data_vacancy['status'] = 3;
			$data_vacancy['uid'] = $arr['uid'];
			$data_vacancy['name'] = $arr['name'];
			$data_vacancy['phone'] = $arr['phone'];
			$data_vacancy['type'] = 0;
			D('House_village_user_vacancy')->where($condition_vacancy)->data($data_vacancy)->save();
			$this->returnCode(0);
    	}else if($aSave === 0){
			$this->returnCode('20090007');
    	}else{
			$this->returnCode('20090011');
    	}
	}
	//	搜索业主
	public function	owner_search(){
		$this->is_existence();
    	//验证权限
    	if(!in_array(91, $this->power)){    		
			$this->returnCode('20090103');
    	}

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
		$condition_where['type'] = array('neq',4);
		$condition_where['parent_id'] = 0;
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
    	//验证权限
    	if(!in_array(92, $this->power)){    		
			$this->returnCode('20090103');
    	}
    	$village_id = I('village_id');

		$arr = array(
			'village_id'	=>	I('village_id'),// 小区id
			// 'usernum'		=>	I('usernum'),//物业编号
			'name'			=>	I('name'), //姓名
			'phone'			=>	I('phone'),//手机号
			'housesize'		=>	I('housesize'),
			// 'water_price'	=>	I('water_price',0),
			// 'electric_price'=>	I('electric_price',0),
			// 'gas_price'		=>	I('gas_price',0),
			// 'park_price'	=>	I('park_price',0),
			// 'property_price'=>	I('property_price',0),
			// 'park_flag'		=>	I('park_flag',0),
			'floor_id'		=>	I('floor_id'), //单元
			'layer_num'		=>	I('layer_num'), //层号
			'room_addrss'		=>	I('room_addrss'),//房号
			'add_time'		=>	time(),
		);
		// if(empty($arr['usernum'])){
		// 	$this->returnCode('20090057');
		// }
		if(empty($arr['name'])){
			$this->returnCode('20090059');
		}
		if(empty($arr['phone'])){
			$this->returnCode('20090060');
		}
		if(empty($arr['floor_id'])){
			$this->returnCode('20090155');
		}
		if(empty($arr['layer_num'])){
			$this->returnCode('20090156');
		}
		if(empty($arr['room_addrss'])){
			$this->returnCode('20090157');
		}
		if(empty($arr['housesize'])){
			$this->returnCode('20090061');
		}

		// 查找单元信息
        $where['floor_id'] = I('floor_id') + 0;
        $where['status'] = 1;
        $where['village_id'] = $village_id;
        $house_village_floor_info = D('House_village_floor')->where($where)->find();
        if (!$house_village_floor_info) {
            $this->returnCode('20090154');
        }

		// 查找房间信息
        $vacancy_where['status'] = 1;
        $vacancy_where['pigcms_id'] = I('layer_room') + 0;
        $vacancy_info = D('House_village_user_vacancy')->where($vacancy_where)->find();
        if (!isset($vacancy_info)) {
            $this->returnCode('20090158');
        }

        $arr['vacancy_id'] = $vacancy_info['pigcms_id']; // 房间id
        $arr['usernum'] = $vacancy_info['usernum']; // 物业编号
		$arr['address'] = $house_village_floor_info['floor_name'] . $house_village_floor_info['floor_layer'] . $arr['layer_num'] . $arr['room_addrss']; //地址

		// 用户id
		$now_user = D('User')->get_user(I('phone'), 'phone');
        if ($now_user) {
            $arr['uid'] = $now_user['uid'];
        }else{
			$arr['uid'] = 0;
		}

		// 查询是否有已绑定住业主
		$aFind = M('House_village_user_bind')->field(array('pigcms_id'))->where(array('village_id'=>$arr['village_id'],'usernum'=>$arr['usernum']))->find();
		if($aFind){
			$this->returnCode('20090058');
		}

		$add = M('House_village_user_bind')->data($arr)->add();
		if($add){
			//更改房间房主信息  uid,name,phone,type=0,status=3,housesize,park_flag,add_time,				
			$data_info['uid'] = $arr['uid'];
			$data_info['name'] = $arr['name'];
			$data_info['phone'] = $arr['phone'];
			$data_info['type'] = 0;
			$data_info['status'] = 3;
			$data_info['housesize'] = $arr['housesize'];
			
			$where_info['pigcms_id'] = I('layer_room');
			$where_info['village_id'] = $village_id;

			D('House_village_user_vacancy')->data($data_info)->where($where_info)->save();
			$this->returnCode(0);
		}else{
			$this->returnCode('20090063');
		}
	}

	// 删除业主
    public function del(){
		$this->is_existence();
      //验证权限
    	if(!in_array(94, $this->power)){    		
			$this->returnCode('20090103');
    	}
        
		$pigcms_id = I('pigcms_id');
		if(empty($pigcms_id)){
			$this->returnCode('20090009');
		}

        $database_house_village_user_bind = D('House_village_user_bind');
        $bind_condition['pigcms_id'] = $pigcms_id;
        $bind_condition['parent_id'] = 0;
        $now_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
        if(!$now_bind_info){
			$this->returnCode('20090010');
        }

        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($now_bind_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        $insert_id = $database_house_village_user_bind->where($bind_condition)->delete();
        fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        // 查询一下是否存在其他状态的业主信息，全部删除
        $where = array('village_id' => $now_bind_info['village_id'], 'room_addrss' => $now_bind_info['room_addrss'],'layer_num' => $now_bind_info['layer_num'],'floor_id' => $now_bind_info['floor_id'], 'type' => array('in', '0, 3'));
        $other = $database_house_village_user_bind->where($where)->field('pigcms_id')->select();
        if (!empty($other)) {
            foreach ($other as $val) {
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($val,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                $database_house_village_user_bind->where(array('pigcms_id' => $val['pigcms_id']))->delete();
                fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            }
        }
        if($insert_id){
			$family_condition['village_id'] = $now_bind_info['village_id'];
			$family_condition['parent_id'] = $now_bind_info['pigcms_id'];
            fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            $user_dels = $database_house_village_user_bind->where($family_condition)->select();
            fdump($user_dels,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
			$database_house_village_user_bind->where($family_condition)->delete();
            fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
			
			//清空房间
			$database_house_village_user_vacancy = D('House_village_user_vacancy');
			$data['uid'] = 0;
			$data['status'] = 1;
			$data['name'] = "";
			$data['phone'] = "";
			$data['type'] = 0;
			$data['park_flag'] = 0;
            $database_house_village_user_vacancy->where(array('phone'=>$now_bind_info['phone'],'pigcms_id'=>$now_bind_info['vacancy_id'],'village_id'=>$now_bind_info['village_id']))->data($data)->save();
			$this->returnCode(0);
        }else{
            $this->returnCode('20090149');
        }
    }

    // 单元信息
    public function floor_info(){
		$this->is_existence();
    	$database_house_village_floor = D('House_village_floor');
        $condition['village_id'] = I('village_id');
        $condition['status'] = 1;
        $floor_list = $database_house_village_floor->house_village_floor_page_list($condition , true ,'floor_id desc' , 99999);
        if ($floor_list['list']['list']) {
        	$this->returnCode(0,$floor_list['list']['list']);
        }else{
        	$this->returnCode('20090150');
        }
        
    }

    // 房间信息
    public function layer_info(){
		$this->is_existence();
    	$database_house_village_floor = D('House_village_floor');
        $condition['village_id'] = I('village_id');
        $condition['status'] = 1;

        $floor_id = I('floor_id');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $where['floor_id'] = $floor_id;
        $where['status'] = 1;
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where ,true ,'pigcms_id desc' , 999999999);
        if ($result['result']['list']) {
        	$this->returnCode(0,$result['result']['list']);
        }else{
        	$this->returnCode('20090151');
        }
        
    }

    //获取业主信息
    public function user_list(){
        // $find_type = I('find_type');
        $find_value = I('find_value');
        $village_id = I('village_id');

        if ($find_value) {
            // if ($find_type == 1) {
                // $where['name'] = array('like', $find_value . '%');
            // } else if ($find_type == 2) {
                // $where['phone'] = array('like', $find_value . '%');
            // } else if ($find_type == 3) {
                // $where['usernum'] = array('like', $find_value . '%');
            // }
            $where['other'] = '(`b`.`name` like "%'.$find_value.'%" OR `b`.`phone` like "%'.$find_value.'%" OR `b`.`usernum` like "%'.$find_value.'%")';
        }
       
        // if($_POST['room_addrss']){
        //     $where['room_addrss'] = array('like', '%' . $_POST['room_addrss'] . '%');
        // }
        // if($_POST['pigcms_id']){
        //     $where['pigcms_id'] = array('like', '%' . $_POST['pigcms_id'] . '%');
        // }
        if(I('type')){
            $type='true';
        }
                    
        // if (empty($where)) {
        //     $user_list = D('House_village_user_bind')->get_limit_room_list_page($village_id);
        // } else {
            $user_list = D('House_village_user_bind')->get_limit_room_list_page($village_id, 99999, $where,$type);
        // }

        $user_list = $user_list['user_list'];

         $cycle_type = array(
                'Y'=>L_('年'),
                'M'=>L_('月'),
                'D'=>L_('日'),
            );

        foreach ($user_list as $key => $value) {
            // $payment_list = array();
            // $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
            // C('DB_PREFIX').'house_village_payment_standard'=>'ps',
            // C('DB_PREFIX').'house_village_payment'=>'p'))
            // ->where("psb.pigcms_id= '".$value['pigcms_id']."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();

            // foreach ($payment_list as $kk => $vv) {
            //     $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);
            //     $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
            //     $payment_list[$kk]['cycle_type'] = $cycle_type[$vv['cycle_type']];
            // }
            
            // $user_list[$key]['payment_list'] = $payment_list;

        } 
        if ($user_list) {
        	$this->returnCode(0,$user_list);
        }else{
        	$this->returnCode('20090169');
        }
    }

    // 业主审核列表
    public function audit_index(){
    	$this->is_existence();
    	//验证权限
    	if(!in_array(101, $this->power)){    		
			$this->returnCode('20090103');
    	}

    	$village_id	=	I('village_id');
		$page	=	I('page',1);
		$page_coun	=	I('page_coun',10);
		$condition_where['village_id'] = $village_id;
		$condition_where['is_del'] = 0;
		$_GET['page'] = $page;
		// (I('status')!==''&&I('status')!==null)&&$condition_where['status'] = I('status');

		$condition_where['status'] = array('not in','1,3');

		$search	=	I('search');
		if($search){
			$where['name'] = array('like',array('%'.$search.'%'));
			$where['phone'] = array('like',array('%'.$search.'%'));
			$where['usernum'] = array('like',array('%'.$search.'%'));
			$where['_logic'] = 'OR';
			$condition_where['_complex'] = $where;
		}else{
			$condition_where['phone'] = array('neq' , "");
			$condition_where['name'] = array('neq' , "");
		}

        $begin_time = I('begin_time');
        $end_time = I('end_time');
        if ($begin_time && !$end_time) {
            $condition_where['application_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $condition_where['application_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $condition_where['application_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($condition_where, true,'',$page_coun);
        
    	foreach ($result['result']['list'] as &$value) {
			$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
			$value['application_time'] = $value['application_time'] ? date('Y-m-d H:i:s',$value['application_time']) : 0;
        }

		$arr['pageCount'] = $result['result']['pageCount'];
		$arr['page'] = $page;
		$arr['listCount'] = count($result['result']['list']);
		$arr['list'] = $result['result']['list'];
		$this->returnCode(0,$arr);
	} 

	// 业主审核详情
    public function audit_detail(){
    	$this->is_existence();
    	//验证权限
    	if(!in_array(101, $this->power)){	
			$this->returnCode('20090103');
    	}

    	$village_id	=	I('village_id');
		$pigcms_id	=	I('pigcms_id');
		$condition_where['village_id'] = $village_id;
		$condition_where['pigcms_id'] = $pigcms_id;

		if (!$pigcms_id) { // 审核ID不存在！
			$this->returnCode('20090185');
		}

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_detail($condition_where);

		if(!$result['detail']){ // 审核信息不存在！
			$this->returnCode('20090186');
		}
		$result['detail']['address'] = $result['detail']['floor_name'].$result['detail']['floor_layer'].$result['detail']['layer'].$result['detail']['room'];
		$this->returnCode(0,$result['detail']);
	}

    // 审核业主列表 编辑
    public function audit_edit(){
        fdump('审核业主列表>原社区管理APP>>'.__LINE__,'api/log/'.date('Ymd').'/h_user/audit_edit_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/h_user/audit_edit_log',true);
    	$this->is_existence();
    	//验证权限
    	if(!in_array(102, $this->power)){	
			$this->returnCode('20090103');
    	}
        
		$pigcms_id	=	intval(I('pigcms_id'));
    	$village_id	=	I('village_id');

        if (!$pigcms_id) {
			$this->returnCode('20090185');
        }

		// 小区信息
		$now_village = M('House_village')->where(array('village_id'=>$village_id))->find();
        // 房屋面积处理
        $housesize = I('housesize');
        $condition_where['pigcms_id'] = $pigcms_id;
        $condition_where['village_id'] = $village_id;
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($condition_where);
        if (!$info['detail']) {
			$this->returnCode('20090186');
        }
        $info = $info['detail'];
        if ($info && $info['village_id']!=$village_id) {
            fdump($village_id,'api/log/'.date('Ymd').'/h_user/audit_edit_log',true);
            fdump($info,'api/log/'.date('Ymd').'/h_user/audit_edit_log',true);
            $this->returnCodeError('当前信息有误，请您暂时移至pc端处理');
        }

        $usernum = I('usernum');
        $name = I('name');
        $phone = I('phone');
        $status = intval(I('status'));
        $floor_id = intval(I('floor_id'));
        $layer_num = I('layer');
        $room_num = I('room');
        $address = I('address');
        $memo = htmlspecialchars(trim(I('memo')));
            
        if(!$usernum){ // 业主编号不能为空！
			$this->returnCode('20090057');
        }

        if(!$name){ // 业主名不能为空！
			$this->returnCode('20090059');
        }

        if(!$phone){ // 手机号不能为空！
			$this->returnCode('20090060');
        }

        $database_house_village_floor = D('House_village_floor');
        $database_house_village_user_bind = D('House_village_user_bind');
        $database_user = D('User');

        // 单元信息
        $where['floor_id'] = $floor_id;
        $where['status'] = 1;
        $where['village_id'] = $village_id;
        $house_village_floor_info = $database_house_village_floor->where($where)->find();

        //检测用户是否已存在 业主
        $Map['vacancy_id'] =  $pigcms_id;
        $Map['type'] = 0;
        $find_info = $database_house_village_user_bind->where($Map)->count();
        $now_user = $database_user->get_user($phone, 'phone');
        if($status == 1){
            if (!$house_village_floor_info) { // 单元信息不存在
				$this->returnCode('20090154');
            }

            // 验证手机号是否和家属一样 先有家属   
            // 手机号不能与家属手机号一样 
            $bind_condition2['vacancy_id'] = $pigcms_id;
            $bind_condition2['phone'] = $phone;
            $bind_condition2['type'] = array('in' , '1,2');;
            $bind_condition2['status'] = array('in' , '1,2');
        
            $family_bind_info = $database_house_village_user_bind->where($bind_condition2)->find();
            if($family_bind_info){ // 该手机号已绑定或已申请绑定此房间
                $this->returnCode('20090189');
            }

            if ($now_user) {
                $data['uid'] = $now_user['uid'];
            }

        	$data['usernum'] = $usernum;
            $data['name'] = $name;
            $data['phone'] = $phone;
            $data['floor_id'] = $floor_id;
            $data['memo'] = $memo ? $memo : '';
            $data['address'] = $address;
            $data['village_id'] = $village_id;

            $data['layer_num'] = $info['layer'];
            $data['room_addrss'] =  $info['room'];
            // 传过来的面积不同就改值
            if (intval($housesize) != intval($info['housesize'])) {
                $data['housesize'] = $housesize;
            } else {
                $data['housesize'] = $info['housesize'];
            }
            $data['park_flag'] = $info['park_flag'];
            $data['add_time'] = time();
            $data['vacancy_id'] = $info['pigcms_id'];
            $data['type'] = $info['type'];
            $data['status'] = 1;
			
			if($find_info>0){
				$insert_id = $database_house_village_user_bind->where($Map)->data($data)->save();	
			}else{
				$insert_id = $database_house_village_user_bind->data($data)->add();
			}
            if($insert_id){
				$data_room['status'] = 3;
                $data_room['uid'] = $data['uid'];
				$data_room['name'] = $name;
				$data_room['phone'] = $phone;
				$data_room['memo'] = $memo ? $memo : "";
                // 传过来的面积不同就改值
                if (intval($housesize) != intval($info['housesize'])) {
                    $data_room['housesize'] = $housesize;
                }
				$database_house_village_user_vacancy->where($condition_where)->data($data_room)->save();  

                //发送模板消息
                if ($now_user['openid']) {
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => L_('您在【X1】的业主入住申请审核已通过\n',$now_village['village_name']), 'keyword1' => L_('业主入住申请'),  'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                }
                $this->returnCode(0,L_('审核成功！'));
            }else{
                $this->returnCode('20090190');
            }
        }else{ // 审核不通过
            // 更新房屋信息
        	$reason= htmlspecialchars(trim(I('reason')));
            $edit_data['status'] = 0;
            $edit_data['reason'] = $reason ? $reason : L_('无');
            $insert_id = $database_house_village_user_vacancy->where($condition_where)->data($edit_data)->save();
           
            if($find_info>0){ //存在业主信息 
                // 查询有无家属信息
                $bind_info = $database_house_village_user_bind->where(array('type'=>array('in',array(1,2)),'vacancy_id'=>$pigcms_id))->count();
                if ($bind_info) { // 更新为虚拟房主
                    $edit_data = array();
                    $edit_data['uid'] = 0;
                    $edit_data['name'] = '';
                    $edit_data['phone'] = '';
                    $edit_data['status'] = 1;
                    $save_bind = $database_house_village_user_bind->where(array('vacancy_id'=>$pigcms_id,'type'=>0))->data($edit_data)->save();
                }else{
                    // 删除房主信息
                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $user_del = $database_house_village_user_bind->where(array('vacancy_id'=>$pigcms_id,'type'=>0))->select();
                    fdump($user_del,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $save_bind = $database_house_village_user_bind->where(array('vacancy_id'=>$pigcms_id,'type'=>0))->delete();
                    fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                }
            }
			
            if($insert_id!==false){
                //发送模板消息
                if ($now_user['openid']) {
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => L_('您在【X1】的业主入住申请审核未通过。原因：X2\n',array('X1'=>$now_village['village_name'],'X2'=>$edit_data['reason'])), 'keyword1' => L_('业主入住申请'), 'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                }
                $this->returnCode(0,L_('审核成功！'));
            }else{
                $this->returnCode('20090190');
            }
        }
    }

	// 审核业主列表 删除
    public function audit_delete(){
    	$this->is_existence();
        //审核业主列表-删除 权限
    	//验证权限
    	if(!in_array(268, $this->power)&&!in_array(94, $this->power)){	
			$this->returnCode('20090103');
    	}

        
		$pigcms_id	=	intval(I('pigcms_id'));
    	$village_id	=	I('village_id');

        if (!$pigcms_id) {
			$this->returnCode('20090185');
        }

        $condition_where['pigcms_id'] = $pigcms_id;
        $condition_where['village_id'] = $village_id;
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($condition_where);

        if (!$info['detail']) {
			$this->returnCode('20090186');
        }

        if ($info['detail']['status'] != 0) { // 该审核信息不能删除
			$this->returnCode('20090187');
        }

        $data['name'] = '';
        $data['phone'] = '';
        $data['uid'] = 0;
        $data['status'] = 1;
        $del = $database_house_village_user_vacancy->where($condition_where)->data($data)->save();
        if ($del) {
            $where = array(
                'village_id'=>$village_id,
                'vacancy_id'=>$pigcms_id,
                'type'=>0,
            );
            $user_bind = D('House_village_user_bind')->where($where)->select();
            if ($user_bind) { //删除绑定业主信息
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($user_bind,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                $user_bind = D('House_village_user_bind')->where($where)->delete();
                fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump(D('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump(D('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            }
			$this->returnCode(0,L_('删除成功'));
        }else{
            $this->returnCode('20090188');
        }        
    }

    // 家属审核列表-查看 
    public function bind_index(){
    	$this->is_existence();
    	//验证权限
    	if(!in_array(103, $this->power) && !in_array(115, $this->power)){    	 	
			$this->returnCode('20090103');
    	}

    	$village_id	=	I('village_id');
		$page	=	I('page',1);
		$_GET['page'] = $page;
		$page_coun	=	I('page_coun',10);
		$where['village_id'] = $village_id;
        // 状态
		I('status')&&$where['status'] = I('status');
		I('pigcms_id')&&$where['parent_id'] = I('pigcms_id'); // 业主id
        if (!isset($where['status'])) {
            $where['status'] = 2; // 如果没有传值状态 默认取待审核
        }

        $where['village_id'] = $village_id;

        $search	=	I('search');
		if($search){
			$or_where['name'] = array('like',array('%'.$search.'%'));
			$or_where['phone'] = array('like',array('%'.$search.'%'));
			$or_where['address'] = array('like',array('%'.$search.'%'));
			$or_where['_logic'] = 'OR';
			$where['_complex'] = $or_where;
		}
        $where['type'] = array('in',array(1,2,3));


        // $find_type = I('type');
        // $find_value = I('value');


        // if ($find_value) {
        //     switch ($find_type) {
        //         case 1: // 家属姓名
        //             $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
        //             $where['type'] = array('in',array(1,2,3));
        //             break;
        //         case 2: // 家属手机号
        //             $where['phone'] = array('like', '%' . $find_value . '%');
        //             $where['type'] = array('in',array(1,2,3));
        //             break;
        //         case 3: // 业主姓名
        //             // 查询业主信息
        //             $parent_where = $where;
        //             $parent_where['name'] = array('like', '%' . $find_value . '%');
        //             $parent_where['parent_id'] = 0;
        //             $parent_where['type'] = 0;
        //             $parent_list = D('House_village_user_bind')->where($parent_where)->field('pigcms_id')->select();
        //             if ($parent_list) {
        //                 $parent_ids = array(); // 业主id
        //                 foreach ($parent_list as $key => $value) {
        //                    $parent_ids[] = $value['pigcms_id'];
        //                 }
        //                 $where['parent_id'] = array('in' , $parent_ids);
        //             } else {
        //                 $where['parent_id'] = -1;
        //             }
        //             break;
        //         case 4: // 业主手机号 
        //             // 查询业主信息
        //             $parent_where = $where;
        //             $parent_where['phone'] = array('like', '%' . $find_value . '%');
        //             $parent_where['parent_id'] = 0;
        //             $parent_where['type'] = 0;
        //             $parent_list = D('House_village_user_bind')->where($parent_where)->field('pigcms_id')->select();
        //             if ($parent_list) {
        //                 $parent_ids = array(); // 业主id
        //                 foreach ($parent_list as $key => $value) {
        //                    $parent_ids[] = $value['pigcms_id'];
        //                 }
        //                 $where['parent_id'] = array('in' , $parent_ids);
        //             } else {
        //                 $where['parent_id'] = -1;
        //             }
        //             break;
        //     }
        // } else {
        //     $where['type'] = array('in',array(1,2,3));
        // }


        $begin_time = I('begin_time');
        $end_time = I('end_time');
        if ($begin_time && !$end_time) {
            $where['add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }

        $result = D('House_village_user_bind')->get_all_child_list($where,true,'status desc,pigcms_id desc');

        foreach ($result['result']['list'] as &$value) {
			$value['pass_time'] = $value['pass_time'] ? date('Y-m-d H:i:s',$value['pass_time']) : '';
        	// if ($value['status'] == 1 && !$value['pass_time']) { // 审核通过没有审核时间
        	// 	$value['pass_time'] = $value['add_time'];
        	// }
        }

		$arr['pageCount'] = $result['result']['pageCount'];
		$arr['page'] = $page;
		$arr['list'] = $result['result']['list'];
		$arr['listCount'] = count($result['result']['list']);

		// if(!$arr['list']){
		// 	$arr = (object)array();
		// }
		$this->returnCode(0,$arr);
	}

	// 家属信息
    public function bind_detail(){
    	$this->is_existence();
		//验证权限
		if(!in_array(103, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$pigcms_id	=	I('pigcms_id');
		
		$child_info =  D('House_village_user_bind')->get_one_by_bindId($pigcms_id);
		$child_info['add_time'] = date('Y-m-d H:i:s',$child_info['add_time']);
		$child_info['pass_time'] = $child_info['pass_time'] ? date('Y-m-d H:i:s',$child_info['pass_time']) : 0;
		if(!$child_info){ // 家属信息不存在
		    $this->returnCode('20090191');
		}
		$this->returnCode(0,$child_info);		     
    }

	// 添加家属信息
    public function bind_add(){
    	$this->is_existence();
		//验证权限
		if(!in_array(110, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$pigcms_id	=	I('pigcms_id');
		$phone	=	I('phone');
		$name	=	I('name');
		$type	=	intval(I('type'));
		$memo	=	htmlspecialchars(trim(I('memo')));
		$bind_info =  D('House_village_user_bind')->get_one_by_bindId($pigcms_id);

		if(!$bind_info){ // 业主信息不存在
		    $this->returnCode('20090169');
		}
		if(empty($name)){
		    $this->returnCode('20090059');
		}
		if(empty($phone)){
		    $this->returnCode('20090060');
		}
		if($phone==$bind_info['phone']){ // 手机号码不能跟业主的手机号码相同
		    $this->returnCode('20090192');
		}

		$user_info = D('User')->where(array('phone'=>$phone))->find();
		if(empty($user_info)){ // 提交失败，用户不是平台用户，请先注册平台会员。
		    $this->returnCode('20090193');
		}
		$add_data['parent_id'] = $pigcms_id;
		$add_data['village_id'] = $village_id;
		$add_data['name'] = $name;
		$add_data['phone'] =  $phone;
		$add_data['type'] = $type;
		$add_data['memo'] = $memo;
        $add_data['status'] = 1;
        $add_data['usernum'] = rand(0,99999) . '-' . time();
        $add_data['add_time'] = time();
		$add_data['pass_time'] = time();
		$insert_id = D('House_village_user_bind')->add($add_data);

		if($insert_id !== false){
		    $this->returnCode(0,L_('添加成功'));
		}else{
		    $this->returnCode('20090190');
		}       
    }

	// 编辑家属信息
    public function bind_edit(){
    	$this->is_existence();
		//验证权限
		if(!in_array(116, $this->power)&&!in_array(104, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$pigcms_id	=	I('pigcms_id');
		$phone	=	I('phone');
		$name	=	I('name');
		$type	=	intval(I('type'));
		$status	=	intval(I('status'));
		$memo	=	htmlspecialchars(trim(I('memo')));
		$reason	    =	htmlspecialchars(trim(I('reason'))) ? htmlspecialchars(trim(I('reason'))) : L_('无');
		$database_house_village_user_bind = D('House_village_user_bind');
		// 小区信息
		$now_village = M('House_village')->where(array('village_id'=>$village_id))->find();

		$now_user =  D('House_village_user_bind')->get_one_by_bindId($pigcms_id);
		if(!$now_user){ // 家属信息不存在
		    $this->returnCode('20090191');
		}

		$bind_info =  D('House_village_user_bind')->get_one_by_bindId($now_user['parent_id']);
		if(!$bind_info){ // 业主信息不存在
		    $this->returnCode('20090169');
		}
		if(empty($name)){
		    $this->returnCode('20090059');
		}
		if(empty($phone)){
		    $this->returnCode('20090060');
		}
		if($phone==$bind_info['phone']){ // 手机号码不能跟业主的手机号码相同
		    $this->returnCode('20090192');
		}

		 // 平台用户
        $user_info = D('User')->get_user($phone, 'phone');
		if(empty($user_info)){ // 提交失败，用户不是平台用户，请先注册平台会员。
		    $this->returnCode('20090193');
		}
		$edit_data = array();
		$where = array();
		$where['pigcms_id'] = $pigcms_id;
		$edit_data['name'] = $name;
		$edit_data['phone'] =  $phone;
		$type && $edit_data['type'] = $type; // 更新房主不传type
		$edit_data['memo'] = $memo;
		// $insert_id = D('House_village_user_bind')->where($where)->save($edit_data);


        if($status == 0){
			if ($now_user['status'] == 0) { // 已解绑！
                // $this->returnCode('20090206');
	            $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($edit_data)->save();
            }else{
            	$edit_data['pass_time'] = time();
				$edit_data['status'] = 0;
				$edit_data['reason'] = $reason;
	            $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($edit_data)->save();

	            if($now_user['type'] != 3){
	            	$temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
	                if ($now_user['status'] == 1 ) {
	                    $first = L_('您在【X1】的X2入住申请已解绑。原因：X3\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name,'X3'=>$reason));
	                    $keyword1 = L_('X1入住申请',$temp_name);
	                }else{
	                    $first = L_('您在【X1】的X2入住申请审核未通过。原因：X3\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name,'X3'=>$reason));
	                    $keyword1 = L_('X1入住申请',$temp_name);                    
	                }
	            }else{
	                $first = L_('您在【X1】的替换房主申请审核未通过。原因：X2\n', array('X1'=>$now_village['village_name'],'X2'=>$reason));
	                $keyword1 = L_('替换房主申请');
	            }
            }

			
        }else{
			if ($now_user['status'] == 1) { // 已通过审核！ 編輯家屬
	            $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($edit_data)->save();
            }else{
            	$edit_data['pass_time'] = time();
				$edit_data['status'] = 1;
				$edit_data['reason'] = $reason;

	            if($now_user['type'] != 3){
	                // 房屋信息
	                $database_house_village_user_vacancy = D('House_village_user_vacancy');
	                $vacancy_condition['pigcms_id'] = $now_user['vacancy_id'];
	                $bind_info = $database_house_village_user_vacancy->where($vacancy_condition)->find();            
	                if($now_user['phone'] == $bind_info['phone']){
	                    if ($bind_info['status']==2) { // 该手机号已提交申请
	                        $this->returnCode('20090195');
	                    }else{ // 手机号不能与业主一致
	                        $this->returnCode('20090196');
	                    }
	                }
	                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($edit_data)->save();

	                $temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
	                $first = L_('您在【X1】的X2入住申请审核已通过\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name));
	                $keyword1 = L_('X1入住申请',$temp_name);
	            }else{
	                // 更新原房主信息
	                $user_data['uid'] = $user_info['uid'];
	                $user_data['name'] = $edit_data['name'];
	                $user_data['phone'] = $edit_data['phone'];
	                $user_data['memo'] = $edit_data['memo'];
					$user_data['status'] = 1;
	                $user_data['pass_time'] = time();
	                $user_data['parent_id'] = 0;
	                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data($user_data)->save();
	                
	                #要修改 房间house_village_user_vacancy 表信息
	                $vacancy_data['uid'] = $user_info['uid'];
	                $vacancy_data['name'] = $edit_data['name'];
	                $vacancy_data['phone'] = $edit_data['phone'];
	                $vacancy_data['status'] = 3;
	                
	                $vacancy_where['pigcms_id'] = $now_user['vacancy_id'];
	                $vacancy_where['floor_id']  = $now_user['floor_id'];
	                $vacancy_where['village_id'] = $now_user['village_id'];
	                $database_house_village_user_vacancy = D('House_village_user_vacancy');
	                $database_house_village_user_vacancy->where($vacancy_where)->data($vacancy_data)->save();

	                // 删除申请记录
                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $user_del = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->find();
                    fdump($user_del,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
	                $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->delete();
                    fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

	                $first = L_('您在【X1】的替换房主申请审核已通过X2\n',array('X1'=>$now_village['village_name'],'X2'=>$reason));
	                $keyword1 = L_('替换房主申请');
	            }
            }

			
        }

        //发送模板消息
        if ($user_info['openid'] && $first) {
            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => $first, 'keyword1' =>  $keyword1,  'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
        }

        if($insert_id){
            $this->returnCode(0,L_('修改成功！'));
        }else{
            $this->returnCode('20090190');
        }      
    }

	// 审核家属信息
    public function bind_audit(){
    	$this->is_existence();
		//验证权限
		if(!in_array(111, $this->power)&&!in_array(105, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$pigcms_id	=	intval(I('pigcms_id'));
		$no_bind	=	intval(I('no_bind'));
		$reason	    =	htmlspecialchars(trim(I('reason'))) ? htmlspecialchars(trim(I('reason'))) : L_('无');

		// 小区信息
		$now_village = M('House_village')->where(array('village_id'=>$village_id))->find();
		
        if(!$pigcms_id){
            $this->returnCode('20090185');
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $now_user = $database_house_village_user_bind->get_one_by_bindId($pigcms_id);
        if(!$now_user){
            $this->returnCode('20090186');
        }

        if(!$no_bind) {
            if ($now_user['status'] == 1) { // 已通过审核！
                $this->returnCode('20090194');
            }
        }	

        // 平台用户
        $user_info = D('User')->get_user($now_user['phone'], 'phone');

        if($no_bind){
            $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data(array('pass_time'=>time(),'status'=>0,'reason'=>$reason))->save();

            if($now_user['type'] != 3){
            	$temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
                if ($now_user['status'] == 1 ) {
                    $first = L_('您在【X1】的X2入住申请已解绑。原因：X3\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name,'X3'=>$reason));
                    $keyword1 = L_('X1入住申请',$temp_name);
                }else{
                    $first = L_('您在【X1】的X2入住申请审核未通过。原因：X3\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name,'X3'=>$reason));
                    $keyword1 =  L_('X1入住申请',$temp_name);                    
                }
            }else{
                $first = L_('您在【X1】的替换房主申请审核未通过。原因：X2\n',array('X1'=>$now_village['village_name'],'X2'=>$reason));
                $keyword1 = L_('替换房主申请');
            }
        }else{
            if($now_user['type'] != 3){
                // 房屋信息
                $database_house_village_user_vacancy = D('House_village_user_vacancy');
                $vacancy_condition['pigcms_id'] = $now_user['vacancy_id'];
                $bind_info = $database_house_village_user_vacancy->where($vacancy_condition)->find();            
                if($now_user['phone'] == $bind_info['phone']){
                    if ($bind_info['status']==2) { // 该手机号已提交申请
                        $this->returnCode('20090195');
                    }else{ // 手机号不能与业主一致
                        $this->returnCode('20090196');
                    }
                }
                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data(array('pass_time'=>time(),'status'=>1))->save();

                $temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
                $first = L_('您在【X1】的X2入住申请审核已通过\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name));
                $keyword1 = L_('X1入住申请',$temp_name);
            }else{
                // 更新原房主信息
                $user_data['uid'] = $now_user['uid'];
                $user_data['name'] = $now_user['name'];
                $user_data['phone'] = $now_user['phone'];
                $user_data['pass_time'] = time();
                $user_data['parent_id'] = 0;
                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data($user_data)->save();
                
                #要修改 房间house_village_user_vacancy 表信息
                $vacancy_data['uid'] = $now_user['uid'];
                $vacancy_data['name'] = $now_user['name'];
                $vacancy_data['phone'] = $now_user['phone'];
                $vacancy_data['status'] = 3;
                
                $vacancy_where['pigcms_id'] = $now_user['vacancy_id'];
                $vacancy_where['floor_id']  = $now_user['floor_id'];
                $vacancy_where['village_id'] = $now_user['village_id'];
                $database_house_village_user_vacancy = D('House_village_user_vacancy');
                $database_house_village_user_vacancy->where($vacancy_where)->data($vacancy_data)->save();

                // 删除申请记录
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                $user_del = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->find();
                fdump($user_del,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->delete();
                fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                $first = L_('您在【X1】的替换房主申请审核已通过X2\n',array('X1'=>$now_village['village_name'],'X2'=>$reason));
                $keyword1 = L_('替换房主申请');
            }
        }

        //发送模板消息
        if ($user_info['openid']) {
            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => $first, 'keyword1' =>  $keyword1,  'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
        }

        if($insert_id){
            $this->returnCode(0,L_('修改成功！'));
        }else{
            $this->returnCode('20090190');
        }    
    }

    // 删除家属审核信息
    public function bind_delete(){
    	$this->is_existence();
		//验证权限
		if(!in_array(113, $this->power)&&!in_array(106, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$pigcms_id	=	intval(I('pigcms_id'));

        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        $user_del = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();
        fdump($user_del,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        if(D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->delete()){
            $this->returnCode(0,L_('删除成功'));
        }else{
            fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump(D('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump(D('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            $this->returnCode('20090197');
        }
    }



	#申请解绑列表 
	public function audit_unbind(){
        //申请解绑列表-查看 权限
    	$this->is_existence();
		//验证权限
		if(!in_array(107, $this->power)){	
			$this->returnCode('20090103');
		}

		$database_house_village_user_unbind = D('House_village_user_unbind');

		$village_id	=	I('village_id');
		$pigcms_id	=	intval(I('pigcms_id'));

		$where['village_id'] = $village_id;

		$page	=	I('page',1);
		$_GET['page'] = $page;
		$page_coun	=	I('page_coun',10);

        $search	=	I('search');
		if($search){
			// $or_where['name'] = array('like',array('%'.$search.'%'));
			// $or_where['phone'] = array('like',array('%'.$search.'%'));
			// $or_where['_logic'] = 'OR';
			$where['_string'] = '(u.name like "%.'.$search.'%" OR u.phone like "%'.$search.'%")';
		}

        $find_status = I('status');
        if($find_status){
            $where['status'] = $find_status;   
        }

        $lists = $database_house_village_user_unbind->get_limit_list_page_new($village_id, $page_coun, $where);
        foreach ($lists['list'] as &$value) {
			$value['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
			$value['edittime'] = date('Y-m-d H:i:s',$value['edittime']);
        }
        $lists['page'] = $page;
        unset($lists['pagebar']);
		$this->returnCode(0 , $lists);
		
	}
	
	#审核用户申请解绑信息	
	public function audit_unbind_detail(){
        //申请解绑列表-查看 权限
    	$this->is_existence();
		//验证权限
		if(!in_array(107, $this->power)){	
			$this->returnCode('20090103');
		}
		
		$database_house_village_user_unbind = D('House_village_user_unbind');			
		$database_house_village_floor = D('House_village_floor');		
		$database_house_village_user_vacancy = D('House_village_user_vacancy');		

		$village_id	=	I('village_id');
		$itemid	=	intval(I('itemid'));
		
		if(!$itemid)  $this->returnCode('20090185');

		$condition['itemid'] = $itemid;
		
		$edit = $database_house_village_user_unbind->where($condition)->find();
		
		$floor_info = $database_house_village_floor->where(array('floor_id'=>$edit['floor_id']))->find();

		$room_info = $database_house_village_user_vacancy->where(array('pigcms_id'=>$edit['room_id']))->find();

		$edit['address'] = $floor_info['floor_name'].$floor_info['floor_layer'].$room_info['layer'].$room_info['room'];
		$edit['addtime'] = date('Y-m-d H:i:s',$edit['addtime']);
		$edit['edittime'] = date('Y-m-d H:i:s',$edit['edittime']);
		$this->returnCode(0 , $edit);
	}

	#修改申请解绑信息	
	public function audit_unbind_edit(){
        //申请解绑列表-查看 权限
    	$this->is_existence();
		//验证权限
		if(!in_array(108, $this->power)){	
			$this->returnCode('20090103');
		}
		
		$database_house_village_user_unbind = D('House_village_user_unbind');			
		$database_house_village_floor = D('House_village_floor');		
		$database_house_village_user_bind = D('House_village_user_bind');
		$database_house_village_user_vacancy = D('House_village_user_vacancy');
			

		$village_id	=	I('village_id');
		$itemid	=	intval(I('itemid'));
		$status	=	intval(I('status')); // 状态 1 申请中 2 未通过 3 已通过
		$reason	=	htmlspecialchars(trim((I('reason'))));
		
		if(!$itemid)  $this->returnCode('20090185');

		// 小区信息
		$now_village = M('House_village')->where(array('village_id'=>$village_id))->find();
		
		
		$where['itemid'] = $itemid;
		
		$data['status']   = $status;	
		$data['edittime'] = time();
        $data['reason'] = $reason ? $reason : L_('无');
		$save_id = $database_house_village_user_unbind->where($where)->data($data)->save();
		
        $info_where['itemid']     = $itemid;
        $info_where['village_id'] = $village_id;
        $info = $database_house_village_user_unbind->where($info_where)->find();
        // 平台用户
        $user_info = D('User')->get_user($info['phone'], 'phone');

        if (!$status) {
        	$this->returnCode('20090205');
        }
		#审核不通过/审核中 不删除绑定信息
		if($status==1 || $status==2){
            if ($status==2) { // 审核不通过
                //发送模板消息
                if ($user_info['openid']) {
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    if ($info['type'] == 0 || $info['type'] == 3) {
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => L_('您在【X1】的业主解绑申请审核未通过。原因：X2\n',array('X1'=>$now_village['village_name'],'X2'=>$data['reason'])), 'keyword1' => L_('业主解绑申请'), 'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                    }else{
                    	$temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => L_('您在【X1】的X2解绑申请审核未通过。原因：X3\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name,'X3'=>$data['reason'])), 'keyword1' => L_('X1解绑申请',$temp_name), 'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                    }
                   
                }
            }
			
			if($save_id) $this->returnCode( 0 ,L_('操作成功！')); else $this->returnCode('20090190');
		
		# 审核通过 删除绑定
		}elseif($status==3){
            //发送模板消息
			if($save_id){
				#如果是业主 先清空房间绑定信息 vacancy 在删除绑定信息/亲属/租客 bind 
				if($info['type']==0 || $info['type']==3){
                    // 发送模板消息
					if ($user_info['openid']) {
                        $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => L_('您在【X1】的业主解绑申请审核已通过\n',$now_village['village_name']), 'keyword1' => L_('业主解绑申请'), 'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                    }

					$room['status'] = 1;
					$room['uid'] = $room['type'] = $room['park_flag']= 0;
					$room['name'] = $room['phone'] = $room['memo'] = "";
					$room['housesize'] = 0.00;
					
					$room_where['pigcms_id'] = $info['room_id'];
					$room_where['village_id'] = $info['village_id'];
					$reset_id = $database_house_village_user_vacancy->where($room_where)->data($room)->save();
					
					#房间清除完成 删除绑定信息
					if($reset_id){						
						#先删除房主信息						
						$bind_info['uid']        = $info['uid'];
						$bind_info['name']       = $info['name'];
						$bind_info['phone']      = $info['phone'];
						$bind_info['floor_id']   = $info['floor_id'];
						$bind_info['vacancy_id'] = $info['room_id'];
						$bind_info['village_id'] = $info['village_id'];
						$bind_info['village_id'] = $info['village_id'];
						$bind_info['type']       = $info['type'];
						$bind_info['pigcms_id']  = $info['bind_id'];
                        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        $del_0_info = $database_house_village_user_bind->where($bind_info)->select();
                        fdump($del_0_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        $del_0_id = $database_house_village_user_bind->where($bind_info)->delete();
                        fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
					
						#再删除亲属/租客信息
						if($del_0_id){
							$bind_info_1['parent_id'] = $info['bind_id'];
							$bind_info_1['type']      = array('in' , '1,2');
                            fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            $del_1_info = $database_house_village_user_bind->where($bind_info_1)->select();
                            fdump($del_0_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
							$del_1_id = $database_house_village_user_bind->where($bind_info_1)->delete();
                            fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
						}
						$this->returnCode( 0 ,L_('操作成功！')); 
					}else{
						
						$this->returnCode( 0 ,'20090203'); 
					}
					
				#如果是亲属/租客	删除绑定信息 bind
				}elseif($info['type']==1 || $info['type']==2){
                    //发送模板消息
					if ($user_info['openid']) {
                        $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$village_id;
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $temp_name = C('config.open_house_user_name')?L_('亲友'):L_('家属');
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => L_('您在【X1】的X2解绑申请审核已通过\n',array('X1'=>$now_village['village_name'],'X2'=>$temp_name)), 'keyword1' => L_('X1解绑申请',$temp_name), 'keyword2' => L_('已发送'), 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => L_('\n请点击查看详细信息！')),0,$now_village['property_id'],1);
                    }

					$bind_info['uid']        = $info['uid'];
					$bind_info['name']       = $info['name'];
					$bind_info['phone']      = $info['phone'];
					$bind_info['floor_id']   = $info['floor_id'];
					$bind_info['vacancy_id'] = $info['room_id'];
					$bind_info['village_id'] = $info['village_id'];
					$bind_info['village_id'] = $info['village_id'];
					$bind_info['type']       = $info['type'];
					$bind_info['pigcms_id']  = $info['bind_id'];
                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $del_0_info = $database_house_village_user_bind->where($bind_info)->select();
                    fdump($del_0_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $del_0_id = $database_house_village_user_bind->where($bind_info)->delete();
                    fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
					if($save_id) $this->returnCode( 0 ,L_('操作成功！')); else $this->returnCode('20090203');
				}
			
			}else{
				$this->returnCode('20090190');
			}
			
		}
	}
	
	#删除 解绑信息 
	public function audit_unbind_del(){
		//申请解绑列表-查看 权限
    	$this->is_existence();
		//验证权限
		if(!in_array(109, $this->power)){	
			$this->returnCode('20090103');
		}

		$village_id	=	I('village_id');
		$itemid	=	intval(I('itemid'));
		
		if(!$itemid)  $this->returnCode('20090185');
		
		$condition['itemid']     = $itemid;
		$condition['village_id'] = $village_id;
		 
		$database_house_village_user_unbind = D('House_village_user_unbind');
		$delete_id = $database_house_village_user_unbind->where($condition)->delete();
		
		if($delete_id) $this->returnCode(0,L_('删除成功！')); else $this->returnCode('20090204');
		
			
	}

}