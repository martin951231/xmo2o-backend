<?php 
/*
 *2019年08月29日10:10
 *营销活动
 */
class MarketingActivityAction extends BaseAction {
	public function index(){
		$database_marketing_activity = D('Marketing_activity');
		$database_marketing_activity_detail = D('Marketing_activity_detail');

		if (!empty($_GET['keyword'])) {
			if ($_GET['searchtype'] == 'activity_id') {
				$where['activity_id'] = $_GET['keyword'];
			} else if ($_GET['searchtype'] == 'title') {
				$where['title'] = array('like', '%' . $_GET['keyword'] . '%');
			}
		}
		$where['is_del'] = 0;
		isset($_GET['status']) && $_GET['status']>=0 && $where['status'] = $_GET['status'];

		// 区域管理员
        if ($this->system_session['level'] == 1) {
            if ($this->system_session['area_type'] == 3) {
            	$this->error('您没有权限');
            }

           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            
            if ($now_area['area_type'] == 2) {
            	//城市管理员
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }

        if ($_GET['province_idss']) {
            $where_detail['province_id'] = $_GET['province_idss'];
        }

        if ($_GET['city_idss']) {
            $where_detail['city_id'] = $_GET['city_idss'];
        }

        if ($where_detail) {
        	$where_detail['is_del'] = 0;
        	$activity_ids = array_column($database_marketing_activity_detail->where($where_detail)->select(), 'activity_id');
        	if ($where['activity_id']) {
        		if (!in_array($where['activity_id'], $activity_ids)) {
        			$where['activity_id'] = -1;
        		}
        	}else{
        		$where['activity_id'] = array('in',$activity_ids);
        	}
        }

		//排序 /*/
		$order_string = '`activity_id` DESC';
		
		$count_count = $database_marketing_activity->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_count, 15);
		$activity_list = $database_marketing_activity->where($where)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$city_id_arr = array();
		foreach($activity_list as $key=>&$v){
			$v['join_num'] = M('Marketing_activity_join')->where(array('activity_id'=>$v['activity_id']))->count();
			$v['join_num_new'] = M('Marketing_activity_join')->where(array('activity_id'=>$v['activity_id'],'is_new'=>1))->count();
		}
		
		$this->assign('activity_list',$activity_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function add(){
		// 区域管理员
        if ($this->system_session['level'] == 1) {
        	// 可编辑的城市
        	$has_city = array();
            if ($this->system_session['area_type'] == 3) {
            	$this->error('您没有权限');
            }

           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            
            if ($now_area['area_type'] == 2) {
            	//城市管理员
                $has_city[]  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
           		$has_city = array_column(M('Area')->field(true)->where(array('area_pid' => $this->system_session['area_id']))->select(), 'area_id');
            }
        }

		if(IS_POST){
			$data = $_POST;
			$data = array(
				'title' => $_POST['title'],
				'status' => $_POST['status'],
				'add_time' => time(),
			);

			if ($_POST['city']) {
				if ($has_city) {
					$diff = array_diff($_POST['city'], $has_city);
					if ($diff) {
	           			$has_city = array_column(M('Area')->field(true)->where(array('area_id' => array('in',$diff)))->select(), 'area_name');
						$this->error('您没有权限添加城市：'.implode('、',$has_city));
					}
				}
				

				$data_detail = array();
				foreach ($_POST['city'] as $key => $value) {
					if (!$_POST['start_time'][$key] || !$_POST['end_time'][$key]) {
						$this->error('活动起始时间设置必填');
					}
					$_POST['start_time'][$key] = strtotime($_POST['start_time'][$key]);
					$_POST['end_time'][$key] = strtotime($_POST['end_time'][$key]);
					if ($_POST['start_time'][$key] >= $_POST['end_time'][$key]) {
						$this->error('活动起始时间设置有误');
					}
					if (!$_POST['coupon'][$key]) {
						$this->error('请添加优惠券');
					}
					$data_detail[$key] = array(
						'start_time' => $_POST['start_time'][$key],
						'end_time' => $_POST['end_time'][$key],
						'province_id' =>$_POST['province'][$key],
						'city_id' => $value,
						'coupon_ids' => trim($_POST['coupon'][$key],','),
						'add_time' => time()
					);
					fdump($data_detail,'data_detail',1);
				}
			}

			$activity_id = D('Marketing_activity')->add($data);
			// var_dump(D('Marketing_activity')->_sql());
			if ($activity_id) {
				if ($data_detail) {
					foreach ($data_detail as &$detail) {
						$detail['activity_id']= $activity_id;
					}
					D('Marketing_activity_detail')->addAll($data_detail);
				}
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
		}else {

			$return =  D('System_coupon')->cate_platform();
			$this->assign("category",$return['category']);
			$this->display();
		}
	}

	public function edit(){
		// 区域管理员
        if ($this->system_session['level'] == 1) {
        	// 可编辑的城市
        	$has_city = array();
            if ($this->system_session['area_type'] == 3) {
            	$this->error('您没有权限');
            }

           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            
            if ($now_area['area_type'] == 2) {
            	//城市管理员
                $has_city[]  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
           		$has_city = array_column(M('Area')->field(true)->where(array('area_pid' => $this->system_session['area_id']))->select(), 'area_id');
            }
        }

        if ($_GET['activity_id']) {
			$database_marketing_activity = D('Marketing_activity');
			$database_marketing_activity_detail = D('Marketing_activity_detail');
			$info = M('Marketing_activity')->where(array('activity_id'=>$_GET['activity_id']))->find();
			$detail = M('Marketing_activity_detail')->where(array('activity_id'=>$_GET['activity_id'],'is_del'=>0))->select();
			$city_ids = array_column($detail, 'city_id');

			// if ($has_city&&!array_intersect($has_city, $city_ids)) {
			// 	$this->error('您没有权限编辑');
			// }

        }

		if(IS_POST){
			$data = $_POST;
			if (!$_POST['activity_id']) {
				$this->error('参数错误');
			}
			$where['activity_id'] = intval($_POST['activity_id']);
			$where['is_del'] = 0;
			$activity_info = M('Marketing_activity')->where($where)->find($data);

			if (!$activity_info) {
				$this->error('活动不存在或已删除');
			}

			// 活动详情
			$detail_list = M('Marketing_activity_detail')->where($where)->select();
			$detail_arr = array();
			foreach ($detail_list as $key => $value) {
				$detail_arr[] = $value['city_id'];
			}

			$data = array(
				'activity_id' => intval($_POST['activity_id']),
				'title' => $_POST['title'],
				'status' => $_POST['status'],
			);

			$_POST['city'] =  $_POST['city'] ? $_POST['city'] : array();
			if ($_POST['city']) {
				$data_detail = array();
				foreach ($_POST['city'] as $key => $value) {
					if (!$_POST['start_time'][$key] || !$_POST['end_time'][$key]) {
						$this->error('活动起始时间设置必填');
					}
					$_POST['start_time'][$key] = strtotime($_POST['start_time'][$key]);
					$_POST['end_time'][$key] = strtotime($_POST['end_time'][$key]);
					if ($_POST['start_time'][$key] >= $_POST['end_time'][$key]) {
						$this->error('活动起始时间设置有误');
					}
					if (!$_POST['coupon'][$key]) {
						$this->error('请添加优惠券');
					}
					$data_detail[$key] = array(
						'activity_id' => $_POST['activity_id'],
						'start_time' => $_POST['start_time'][$key],
						'end_time' => $_POST['end_time'][$key],
						'province_id' =>$_POST['province'][$key],
						'city_id' => $value,
						'coupon_ids' => trim($_POST['coupon'][$key],','),
					);
				}
			}

			$update = D('Marketing_activity')->save($data);
			// var_dump(D('Marketing_activity')->_sql());
			if ($update!==false) {
				if ($data_detail) {
					foreach ($data_detail as &$detail) {
						// 查看地区是否已经存在，是则更新，不是则添加一条记录
						if (in_array($detail['city_id'], $detail_arr)) {
							D('Marketing_activity_detail')->where(array('city_id'=>$detail['city_id'],'activity_id'=>$_POST['activity_id'],'is_del'=>0))->save($detail);
						}else{
							$detail['add_time'] = time();
							D('Marketing_activity_detail')->add($detail);
							// var_dump(D('Marketing_activity_detail')->_sql());
						}
					}
				}
				$del_detail_arr = array_diff($detail_arr, $_POST['city']);
				if ($del_detail_arr) {
					if ($has_city&&array_diff($del_detail_arr,$has_city)) {
	           			$has_city = array_column(M('Area')->field(true)->where(array('area_id' => array('in',$del_detail_arr)))->select(), 'area_name');
						$this->error('您没有权限删除城市：'.implode('、',$has_city));
					}
					D('Marketing_activity_detail')->where(array('city_id'=>array('in',$del_detail_arr),'activity_id'=>$_POST['activity_id'],'is_del'=>0))->setField('is_del',1);
				}
				$this->success('保存成功！');
			}else{
				$this->error('保存失败！');
			}
		}else {
			$area_list = M('Area')->where(array('area_id'=>array('in',$city_ids)))->select();
			$area_list_tem = array();
			foreach ($area_list as $key => $value) {
				$area_list_tem[$value['area_id']] = $value['area_name'];
			}

			foreach ($detail as $key => $value) {
				$detail[$key]['area_name'] = $area_list_tem[$value['city_id']];

				if ($has_city) {
					if ($has_city&&in_array($value['city_id'], $has_city)) {
						$detail[$key]['is_power'] = 1;
					}else{
						$detail[$key]['is_power'] = 0;
					}
				}else{
						$detail[$key]['is_power'] = 1;
				}
				// 优惠券
				if ($value['coupon_ids']) {
					$detail[$key]['coupon_list'] = M('System_coupon')->where(array('coupon_id'=>array('in',$value['coupon_ids'])))->select();
					foreach ($detail[$key]['coupon_list'] as &$v) {
						$v['platform']=unserialize($v['platform']);
						if($v['cate_name']!='all'&&!empty($v['cate_id'])){
							$tmp = unserialize($v['cate_id']);
							$v['cate_id'] = $tmp['cat_name'];
						}
					}
				}
			}
			$return =  D('System_coupon')->cate_platform();
			$this->assign("category",$return['category']);
			$this->assign("platform",$return['platform']);
			$this->assign("info",$info);
			$this->assign("detail",$detail);
			$this->assign("city_ids",implode(',',$city_ids));
			$this->display();
		}
	}

	public  function  select_city(){
		// 区域管理员
        if ($this->system_session['level'] == 1) {
           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            
            if ($now_area['area_type'] == 2) {
            	//城市管理员
            	$where['area_id'] = $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
            	$where['area_pid'] = $this->system_session['area_id'];
            }
        } 

        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);
		if($selected){
            $selected_arr = explode(',', $selected);
        }

        $where['is_open'] = 1;
        $where['area_type'] = 2;
        $count = D('Area')->field(true)->where($where)->count(); 
        import('@.ORG.diypage');
        $p = new Page($count, 10);
        $city_list = D('Area')->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select(); 

        foreach ($city_list as $key => $value) {
            $city_list[$key]['select'] = 0;
            if(in_array($value['area_id'], $selected_arr)){
                $city_list[$key]['select'] = 1;
            }
        }
        // var_dump($city_list);

        $this->assign('city_list',$city_list);
        $this->display();
	}

	public  function  select_coupon(){
		
        $selected = trim(I("selected"),',');
        $city_id = intval(I("city_id"));

    	$where['_string'] = '(city_id = '.$city_id.' OR city_id=0)';
    	$where['status'] = '1';
        $where['end_time'] = array('gt',time());
            

        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);
		if($selected){
            $selected_arr = explode(',', $selected);
        }

        $count = D('Area')->field(true)->where($where)->count(); 
        import('@.ORG.diypage');
        $p = new Page($count, 10);
        $coupon_list = D('Area')->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select(); 

        foreach ($coupon_list as $key => $value) {
            $city_list[$key]['select'] = 0;
            if(in_array($value['area_id'], $selected_arr)){
                $city_list[$key]['select'] = 1;
            }
        }
        // var_dump($city_list);

        $this->assign('coupon_list',$coupon_list);
        $this->display();
	}

	public  function del_activity(){
		$activity_id = $_POST['activity_id'];
		$activity = M('Marketing_activity')->where(array('activity_id'=>$activity_id))->find();
        
		if (!$activity) {
			$this->error('活动不存在或已删除');
		}

		// 区域管理员
        if ($this->system_session['level'] == 1) {
        	// 可编辑的城市
        	$has_city = array();
            if ($this->system_session['area_type'] == 3) {
            	$this->error('您没有权限');
            }

           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            
            if ($now_area['area_type'] == 2) {
            	//城市管理员
                $has_city[]  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
           		$has_city = array_column(M('Area')->field(true)->where(array('area_pid' => $this->system_session['area_id']))->select(), 'area_id');
            }
        }

		$detail = M('Marketing_activity_detail')->where(array('activity_id'=>$activity_id,'is_del'=>0))->select();
		$city_ids = array_column($detail, 'city_id');
		if ($has_city&&array_diff($city_ids, $has_city)) {
			$this->error('您没有权限删除');
		}

		if(M('Marketing_activity')->where(array('activity_id'=>$activity_id))->setField('is_del',1)){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	

	public function url_qrcode()
	{
		$activity_id = intval($_GET['activity_id']);
		$qrCon = $this->config['site_url'] . '/packapp/westpac/index.html#/?activity_id=' . $activity_id;
		import('@.ORG.phpqrcode');
		$size = $_GET['size'] ? $_GET['size']: 11;
		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);
	}
}
