<?php
/*
 * 社区APP版	登录 2.0
 *
 */
class H_loginAction extends BaseAction{
	//	登录
    public function login(){
    	$ticket = I('ticket', false);
        $database_house = D('House_village'); 
        $database_admin = D('House_admin');
        $database_property_admin = D('Property_admin');
        $database_house_property = D('House_property');

        //返回安卓版本信息 Android版本名称：village_android_version Android版本号：android_version_code Android版本描述：android_version_desc Android下载地址：android_download_url

        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $uid = $info['uid']-10000000;
	            $type = substr($uid, 0,1);
	            $uid = substr($uid, 1);
	            if ($type==1) { // 主账号-小区工作人员
                	$now_house = $database_house->field(true)->where(array('village_id'=>$uid))->find(); 

					//权限
                	$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
	                $power = array();
	                foreach ($admin_menus as $value) {
	                    $power[] = $value['id'];
	                }
	            }elseif($type==2){ // 角色-小区工作人员
                    $role = $database_admin->get_info_one(array('id'=>$uid));
                    $now_house = $database_house->field(true)->where(array('village_id'=>$role['village_id']))->find();
                    $now_house['account'] = $role['realname'] ? $role['realname'] : $role['account'];
                    //权限
                    $power = explode(',',$role['menus']);
                }elseif($type==3){ // 物业管理员
                    $role = $database_property_admin->get_one(array($uid));
                    $now_house = $database_house->field(true)->where(array('village_id'=>$role['village_id']))->find();
                    $now_house['account'] = $role['property_name'] ? $role['property_name'] : $role['account'];
                    //权限
                    if ($role['menus']) {
                        $power = explode(',',$role['menus']);
                    } else {
                        $power = array();
                    }
                }elseif($type==4){ // 物业工作人员
                    $condition_property_house = array();
                    $village_id = $_POST['village_id'];
                    if($village_id>0){
                        $condition_property_house['village_id'] = $village_id;
                    }
                    $role = $database_property_admin->where(['id'=>$uid])->find();
                    if (!$village_id) {
                        // 如果这个时候没有小区 默认取用一个
                        if ($role && $role['property_id']>0) {
                            $condition_property_house['property_id'] = $role['property_id'];
                        }
                        $condition_property_house['status'] = ['in', [0,1]];
                        $now_house = $database_house->field(true)->where($condition_property_house)->find();
                    } else {
                        $now_house = $database_house->field(true)->where(array('village_id'=>$village_id))->find();
                    }
                    if (empty($now_house)) {
                        $now_house = [];
                    }
                    $now_house['account'] = $role['realname'] ? $role['realname'] : $role['account'];
                    //权限
                    $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                    $power = array();
                    foreach ($admin_menus as $value) {
                        $power[] = $value['id'];
                    }
                }

	            //底部菜单
	            $footer = $this->getFooter($power);

                unset($now_house['pwd']);

            	// 获得门密码
                $lockPwd = $this->get_lock_pwd($now_house);
                $arr = array(
                	'user'      => $now_house,
                	'footer'      => $footer,
                    'ticket'    => $ticket,
					'lockPwd'    => $lockPwd,
                );
                $this->returnCode(0,$arr);
            }else{
				$this->returnCode('20000009');
            }
        }

		$condition_house['account'] = I('account');

        $account_type = I('account_type', 0, 'intval'); // 账号类型---0 小区工作人员  1 物业管理员 2 物业工作人员

		if($_POST['version_code']>=300 && $account_type==0){
			$village_id = $_POST['village_id'];

			if($village_id>0){
				$condition_house['village_id'] = $village_id;
			}
			$now_house = $database_house->field(true)->where($condition_house)->select();
			if ($now_house) { // 社区超级管理员登录
				if(count($now_house)==1){
					$now_house = $now_house[0];
				}else{
					$this->returnCode(0,array('village_list'=>$now_house));
				}
			}else{ // 角色登录
                $role = $database_admin->get_info_one($condition_house);

				if(empty($role)){
					$this->returnCode('20000005');
				}

				//权限
                $power = explode(',',$role['menus']);

	            //底部菜单
	            $footer = $this->getFooter($power);

				$pwd = md5(I('pwd'));
				if($pwd != $role['pwd']){
					$this->returnCode('20000006');
				}
				if($role['status'] == 2){
					$this->returnCode('20000007');
				}
                $now_house = $database_house->field(true)->where(array('village_id'=>$role['village_id']))->find();
				unset($now_house['pwd']);
				$role_id = '2'.$role['id']+10000000;
				$ticket = ticket::create($role_id, $this->DEVICE_ID, true);
            	$now_house['account'] = $role['realname'] ? $role['realname'] : $role['account'];

            	// 获得门密码
                $lockPwd = $this->get_lock_pwd($now_house);
		        $arr	=	array(
					'user'	=>	$now_house,
                	'footer'      => $footer,
					'ticket'	=>	$ticket['ticket'],
					'lockPwd'    => $lockPwd,
		        );
				$data_house['id'] = $role['id'];
                $data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				if($database_admin->data($data_house)->save()){
					// 更新设备号
		            $this->upDevice(2, $role['id']);

					$this->returnCode(0,$arr);
				}else{
					$this->returnCode('20000008');
				}
			}
		} elseif ($_POST['version_code']>=300 && $account_type==1){
            $condition_property_house = array();
            $village_id = $_POST['village_id'];
            if($village_id>0){
                $condition_property_house['village_id'] = $village_id;
            }
            $condition_house_property['account'] = trim(I('account'));
            $house_property = $database_house_property->field(true)->where($condition_house_property)->find();
            if(empty($house_property)){
                $this->returnCodeError('账号不存在');
            }
            if($house_property['status'] != 1){
                $this->returnCode('20000007');
            }
            if ($house_property && $house_property['id']>0) {
                $condition_property_house['property_id'] = $house_property['id'];
            }
            $condition_property_house['status'] = ['in', [0,1]];

            $now_house = $database_house->field(true)->where($condition_property_house)->select();
            if ($now_house) { // 社区超级管理员登录
                if(count($now_house)==1){
                    $now_house = $now_house[0];
                    //权限
//                    if ($house_property['menus']) {
//                        $power = explode(',',$house_property['menus']);
//                    } else {
//                        $power = array();
//                    }
                    $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                    $power = array();
                    foreach ($admin_menus as $value) {
                        $power[] = $value['id'];
                    }

                    //底部菜单
                    $footer = $this->getFooter($power);

                    $pwd = md5(I('pwd'));
                    if($pwd != $house_property['password']){
                        $this->returnCode('20000006');
                    }
                    $now_house = $database_house->field(true)->where(array('village_id'=>$now_house['village_id']))->find();
                    unset($now_house['pwd']);
                    $role_id = '3'.$house_property['id']+10000000;
                    $ticket = ticket::create($role_id, $this->DEVICE_ID, true);
                    $now_house['account'] = $house_property['property_name'] ? $house_property['property_name'] : $house_property['account'];

                    // 获得门密码
                    $lockPwd = $this->get_lock_pwd($now_house);
                    $arr	=	array(
                        'user'	=>	$now_house,
                        'footer'      => $footer,
                        'ticket'	=>	$ticket['ticket'],
                        'lockPwd'    => $lockPwd,
                    );
                    $data_house['id'] = $house_property['id'];
                    $data_house['last_time'] = $_SERVER['REQUEST_TIME'];
                    if($database_house_property->data($data_house)->save()){
                        // 更新设备号
                        $this->upDevice(3, $house_property['id']);

                        $this->returnCode(0,$arr);
                    }else{
                        $this->returnCode('20000008');
                    }
                }else{
                    $this->returnCode(0,array('village_list'=>$now_house));
                }
            }else{
                $this->returnCodeError('该账号下暂无小区');
            }
        } elseif ($_POST['version_code']>=300 && $account_type==2){
            $condition_property_house = array();
            $village_id = $_POST['village_id'];
            if($village_id>0){
                $condition_property_house['village_id'] = $village_id;
            }
            $condition_property_admin['account'] = trim(I('account'));
            $property_admin = $database_property_admin->where($condition_property_admin)->find();
            if(empty($property_admin)){
                $this->returnCodeError('账号不存在');
            }
            if($property_admin['status'] == 2){
                $this->returnCode('20000007');
            }
            if ($property_admin && $property_admin['property_id']>0) {
                $condition_property_house['property_id'] = $property_admin['property_id'];
            }
            if(!$village_id){
                $menus = explode(',',$property_admin['menus']);
                $condition_property_house['village_id'] = ['in', $menus];
            }
            $condition_property_house['status'] = ['in', [0,1]];
            $now_house = $database_house->field(true)->where($condition_property_house)->select();
            if ($now_house) { // 社区超级管理员登录
                if(count($now_house)==1){
                    $now_house = $now_house[0];
//                    if ($property_admin['menus']) {
//                        $power = explode(',',$property_admin['menus']);
//                    } else {
//                        $power = array();
//                    }
                    //权限
                    $admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
                    $power = array();
                    foreach ($admin_menus as $value) {
                        $power[] = $value['id'];
                    }

                    //底部菜单
                    $footer = $this->getFooter($power);

                    $pwd = md5(I('pwd'));
                    if($pwd != $property_admin['pwd']){
                        $this->returnCode('20000006');
                    }
                    $now_house = $database_house->field(true)->where(array('village_id'=>$now_house['village_id']))->find();
                    unset($now_house['pwd']);
                    $role_id = '4'.$property_admin['id']+10000000;
                    $ticket = ticket::create($role_id, $this->DEVICE_ID, true);
                    $now_house['account'] = $property_admin['realname'] ? $property_admin['realname'] : $property_admin['account'];

                    // 获得门密码
                    $lockPwd = $this->get_lock_pwd($now_house);
                    $arr	=	array(
                        'user'	=>	$now_house,
                        'footer'      => $footer,
                        'ticket'	=>	$ticket['ticket'],
                        'lockPwd'    => $lockPwd,
                    );
                    $data_house['id'] = $property_admin['id'];
                    $data_house['last_time'] = $_SERVER['REQUEST_TIME'];
                    $data_house['login_count'] = $property_admin['login_count'] + 1;
                    if($database_property_admin->data($data_house)->save()){
                        // 更新设备号
                        $this->upDevice(4, $property_admin['id']);

                        $this->returnCode(0,$arr);
                    }else{
                        $this->returnCode('20000008');
                    }
                }else{
                    $this->returnCode(0,array('village_list'=>$now_house));
                }
            }else{
                $this->returnCodeError('该账号下暂无小区');
            }
        }else{
			$now_house = $database_house->field(true)->where($condition_house)->find();

			if (!$now_house) { // 角色登录
                $role = $database_admin->get_info_one($condition_house);
                // var_dump($database_admin->_sql());

				if(empty($role)){
					$this->returnCode('20000005');
				}

				//权限
                $power = explode(',',$role['menus']);
                
	            //底部菜单
	            $footer = $this->getFooter($power);

				$pwd = md5(I('pwd'));
				if($pwd != $role['pwd']){
					$this->returnCode('20000006');
				}
				if($role['status'] == 2){
					$this->returnCode('20000007');
				}
                $now_house = $database_house->field(true)->where(array('village_id'=>$role['village_id']))->find();
				unset($now_house['pwd']);
				$role_id = '2'.$role['id']+10000000;
				$ticket = ticket::create($role_id, $this->DEVICE_ID, true);
            	$now_house['account'] = $role['realname'] ? $role['realname'] : $role['account'];

            	// 获得门密码
                $lockPwd = $this->get_lock_pwd($now_house);
		        $arr	=	array(
					'user'	=>	$now_house,
                	'footer'      => $footer,
					'ticket'	=>	$ticket['ticket'],
					'lockPwd'    => $lockPwd,
		        );
				$data_house['id'] = $role['id'];
				$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
				if($database_admin->data($data_house)->save()){
					// 更新设备号
		            $this->upDevice(2, $role['id']);

					$this->returnCode(0,$arr);
				}else{
					$this->returnCode('20000008');
				}
			}
		}
		if(empty($now_house)){
			$this->returnCode('20000005');
		}

		//权限
		$admin_menus = D('House_menu_new')->field(true)->where(array('status'=>1))->select();
        $power = array();
        foreach ($admin_menus as $value) {
            $power[] = $value['id'];
        }

        //底部菜单
        $footer = $this->getFooter($power);

		$pwd = md5(I('pwd'));
		if($pwd != $now_house['pwd']){
			$this->returnCode('20000006');
		}
		if($now_house['status'] == 2){
			$this->returnCode('20000007');
		}
		unset($now_house['pwd']);
		$village_id	=	'1'.$now_house['village_id']+10000000;
		$ticket = ticket::create($village_id, $this->DEVICE_ID, true);

    	// 获得门密码
        $lockPwd = $this->get_lock_pwd($now_house);
        $arr = array(
			'user'	=>	$now_house,
            'footer'      => $footer,
			'ticket'	=>	$ticket['ticket'],
            'lockPwd'    => $lockPwd,
        );
		$data_house['village_id'] = $now_house['village_id'];
		$data_house['last_time'] = $_SERVER['REQUEST_TIME'];
		if($database_house->data($data_house)->save()){
			// 更新设备号
            $this->upDevice(1, $now_house['village_id']);
            
			$this->returnCode(0,$arr);
		}else{
			$this->returnCode('20000008');
		}
	}

    // 获得首页底部菜单
	function getFooter($menu=array()){
		$arr = array(
			// 底部菜单
			'is_show' => false,
			'child' => array(
				'cashier' =>false, //收银台
				'door' =>false, //智慧门禁
			)
		);
		// 角色权限
		foreach ($menu as $value) {
			switch ($value) {
				case '66':
					$arr['is_show'] = true;
					$arr['child']['cashier'] = true;
					break;
				case '226':
					$arr['is_show'] = true;
					$arr['child']['door'] = true;
					break;
			}
		}
		return $arr;
	}


    //  更新user表里的Device-Id
    public function upDevice($type,$id){
        $userUpdata =   array(
            'device_id' =>  $this->DEVICE_ID,
        );
        if ($type==1) { // 总管理员
       	 	M('House_village')->where(array('village_id' => $id))->save($userUpdata);
        }elseif ($type==3){
            M('House_property')->where(array('id' => $id))->save($userUpdata);
        }elseif ($type==4){
            M('Property_admin')->where(array('id' => $id))->save($userUpdata);
        }else{
            M('House_admin')->where(array('id' => $id))->save($userUpdata);
        }
    }

    //  获得开门密码
    public function get_lock_pwd($now_house){
        if ($now_house['lock_pwd']) {
        	return $now_house['lock_pwd'];
        }else{
        	$lock_pwd = mt_rand(10000000,99999999);
       	 	M('House_village')->where(array('village_id' => $now_house['village_id']))->save(array('lock_pwd'=>$lock_pwd));
       	 	return $lock_pwd;
        }
    }
}