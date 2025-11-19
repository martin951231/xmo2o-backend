<?php
/*
 * 社区首页
 *
 */
class RepairAction extends BaseAction{
	protected $village_id;
	protected $village;

	public function _initialize(){
		parent::_initialize();

		$this->village_id = $this->house_session['village_id'];
		$this->village = D('House_village')->where(array('village_id'=>$this->village_id))->find();
		if(empty($this->village)){
			$this->error('该'.C('config.house_name').'不存在！');
		}
		$works_order_switch=D('House_village_info')->where(['village_id'=>$this->village_id])->getField('works_order_switch');
		if($works_order_switch == 1){
            $this->error('已开启新版工单，暂不支持老版工单！');
        }elseif($works_order_switch == 2){
            if(in_array(strtolower(ACTION_NAME),['add_repair','add_water','add_suggest'])){
                $this->error('已开启新/版工单清晰版，暂不支持添加老版工单数据！');
            }
        }
	}

    //在线报修提醒
    public function repair_reminder(){
    	//在线报修-查看 权限
        if (!in_array(219, $this->house_session['menus'])) {
            exit(json_encode(array('error'=>0,'msg'=>'对不起，您没有权限执查看在线报修信息')));
        }
        $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('repair_reminder');
        if($sum > 0){
            M('House_village')->where(array('village_id'=>$this->village_id))->setField('repair_reminder',0);
            exit(json_encode(array('error'=>1,'msg'=>'您有 ('.$sum.') 在线报修提醒！')));
        }else{
            exit(json_encode(array('error'=>0,'msg'=>'暂无在线报修信息')));
        }
    }

    //投诉建议提醒
    public function suggest_reminder(){
    	//投诉建议-查看 权限
        if (!in_array(224, $this->house_session['menus'])) {
            exit(json_encode(array('error'=>0,'msg'=>'对不起，您没有权限执查看投诉建议信息')));
        }
        $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('suggest_reminder');
        if($sum > 0){
            M('House_village')->where(array('village_id'=>$this->village_id))->setField('suggest_reminder',0);
            exit(json_encode(array('error'=>1,'msg'=>'您有 ('.$sum.') 投诉建议提醒！')));
        }else{
            exit(json_encode(array('error'=>0,'msg'=>'暂无投诉建议信息')));
        }
    }

    public function index()
    {
        //在线报修-查看 权限
        if (!in_array(219, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['usernum'] = $_GET['find_value'];
					break;
				case 2:
					$where['phone'] = $_GET['find_value'];
					break;
				case 3:
					$where['address'] = $_GET['find_value'];
					break;
				default:
					break;
			}
		}


		$status = $_GET['status'] + 0;
		if($status > 0){
			$where['status'] = $status - 1;
		}

		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['begin_time'] = $begin_time;
			$where['end_time'] = $end_time;
		}elseif(isset($begin_time)){
			$where['begin_time'] = $begin_time;
		}elseif(isset($end_time)){
			$where['end_time'] = $end_time;
		}

		$where['village_id'] = $this->village_id;
		$where['type'] = 1;

		if($_GET['time']){
			$order['time'] = $_GET['time'];
		}

		if ($_GET['cate1']) {
			$where['repair_type'] = $_GET['cate1'];
		}
		if ($_GET['cate']) {
			$where['cate_fid'] = $_GET['cate'];
		}

		if ($_GET['subcate']) {
			$where['cate_id'] = $_GET['subcate'];
		}
		$cate_where = array(
			'village_id'=>$where['village_id'],
			'parent_id'=>0,
			'status'=>1,
			'type'=>1
		);
		$cate_list = M('House_village_repair_cate')->where($cate_where)->getField('id,cate_name');
		$this->assign('cate_list',$cate_list);
		$repair_list = D('House_village_repair_list')->getlist($where , 20 , $order);
		if($repair_list['repair_list']){
			foreach($repair_list['repair_list'] as $k=>&$v){
				if(empty($v['address']) || empty($v['usernum'])){
					$user_bind = D('House_village_user_bind')->where(array('uid'=>$v['uid'],'type'=>['in',[0,3]],'status' => 1,'village_id'=>$v['village_id']))->find();
					//$v['address'] = $user_bind['address'];
                    $v['usernum'] = $user_bind['usernum'];
                    $v['bind_number'] = $user_bind['bind_number'];
				}
                if($v['classify_id']){

                    $v['repair_type'] =D('house_village_repair_classify')->where(['id'=>$v['classify_id']])->getField('cate_name');
                    $v['cate_name']=D('House_village_repair_list')->get_repair_cate($v);
                    /*if($v['cate_id']){
                        $repai_cate = M('House_village_repair_cate')->where(array('id'=>$v['cate_id']))->find();
                        $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$repai_cate['parent_id']))->find();
                        if($repai_f_cate && $repai_cate){
                            $v['cate_name'] = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
                        }elseif($repai_f_cate){
                            $v['cate_name'] = $repai_f_cate['cate_name'];
                        }else{
                            $v['cate_name'] = '-';
                        }

                    }elseif($v['cat_fid']){
                        $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$v['cat_fid']))->find();
                        if($repai_f_cate){
                            $v['cate_name'] = $repai_f_cate['cate_name'];
                        }else{
                            $v['cate_name'] = '-';
                        }
                    }else{
                        $v['cate_name'] = '-';
                    }*/

                }else{
                    if($v['repair_type']){
                        $arr = array(
                            '0'=>'个人维修',
                            '1'=>'公共维修',
                            '2'=>'个人维修'
                        );
                        $v['repair_type'] = $arr[$v['repair_type']];
                        $v['cate_name']=D('House_village_repair_list')->get_repair_cate($v);
                       /* if($v['cate_id']){
                            $repai_cate = M('House_village_repair_cate')->where(array('id'=>$v['cate_id']))->find();
                            $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$repai_cate['parent_id']))->find();
                            if($repai_f_cate && $repai_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
                            }elseif($repai_f_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'];
                            }else{
                                $v['cate_name'] = '-';
                            }

                        }elseif($v['cat_fid']){
                            $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$v['cat_fid']))->find();
                            if($repai_f_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'];
                            }else{
                                $v['cate_name'] = '-';
                            }
                        }else{
                            $v['cate_name'] = '-';
                        }*/
                    } else{
                        $v['repair_type'] = '个人维修';
                        $v['cate_name']=D('House_village_repair_list')->get_repair_cate($v);
                        /*if($v['cate_id'] && $v['cat_fid']){
                            $repai_cate = M('House_village_repair_cate')->where(array('id'=>$v['cate_id']))->find();
                            $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$v['cat_fid']))->find();
                            if($repai_f_cate && $repai_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
                            }elseif($repai_f_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'];
                            }else{
                                $v['cate_name'] = '-';
                            }

                        }elseif($v['cat_fid']){
                            $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$v['cat_fid']))->find();
                            if($repai_f_cate){
                                $v['cate_name'] = $repai_f_cate['cate_name'];
                            }else{
                                $v['cate_name'] = '-';
                            }
                        }else{
                            $v['cate_name'] = '-';
                        }*/
                    }
                }


                //根据楼栋id 单元id 楼层id 房号id 获取地址
                $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$this->village_id);

			}
		}
//		var_dump($repair_list);die;
		$this->assign('repair_list', $repair_list);
		if($begin_time && $end_time){
			unset($where['end_time']);
			unset($where['begin_time']);
			$where['time'] = array('between' , array($begin_time,$end_time));
		}

		$repair_list_count = D('House_village_repair_list')->where($where)->count();

		if($repair_list_count){//全部
			$this->assign('repair_list_count', $repair_list_count);
		}else{
			$this->assign('repair_list_count', 0);
		}

		$repair_count_0 = D('House_village_repair_list')->where($where)->where(array('status'=>0))->count();
		if($repair_count_0){//未指派
			$this->assign('repair_count_0', $repair_count_0);
		}else{
			$this->assign('repair_count_0', 0);
		}

		$repair_count_1 = D('House_village_repair_list')->where($where)->where(array('status'=>1))->count();
		if($repair_count_1){//已指派
			$this->assign('repair_count_1', $repair_count_1);
		}else{
			$this->assign('repair_count_1', 0);
		}

		$repair_count_2 = D('House_village_repair_list')->where($where)->where(array('status'=>2))->count();
		if($repair_count_2){//已受理
			$this->assign('repair_count_2', $repair_count_2);
		}else{
			$this->assign('repair_count_2', 0);
		}

		$repair_count_3 = D('House_village_repair_list')->where($where)->where(array('status'=>3))->count();
		if($repair_count_3){//已处理
			$this->assign('repair_count_3', $repair_count_3);
		}else{
			$this->assign('repair_count_3', 0);
		}

		$repair_count_4 = D('House_village_repair_list')->where($where)->where(array('status'=>4))->count();
		if($repair_count_4){//业主已评价
			$this->assign('repair_count_4', $repair_count_4);
		}else{
			$this->assign('repair_count_4', 0);
		}


        $repair_classify=M('House_village_repair_classify')->where(['village_id'=>$this->village_id,'del_time'=>0,'status'=>1])->order('sort desc,id desc')->select();
        $this->assign('classify_list', $repair_classify);
		$this->display();
    }

	public function repair_config()
	{
       if(IS_POST){
		   $data['repair_config'] = $_POST['repair_config'];
		   $res = D('House_village')->where(array('village_id'=>$this->village_id))->data($data)->save();
		   if($res){
			   $this->success('修改成功!');
		   }else{
			   $this->error('修改失败!');
		   }
	   }else{
		   $house_village = D('House_village')->field('repair_config')->where(array('village_id'=>$this->village_id))->find();
           $this->assign('house_village',$house_village);
		   $this->display();
	   }
	}



	public function add_repair()
	{
		if(IS_POST){
			$database_house_village_repair_list = D('House_village_repair_list');
			$data['village_id'] = $this->village_id;
            $property_id = D('House_village')->where(['village_id'=>$this->village_id])->getField('property_id');
			if(empty($_POST['cate1'])){
				$this->error('类型必须选择');
			}
			$data['repair_type'] = $_POST['cate1'];

			if(empty($_POST['subcate'])){

				if(empty($_POST['cate'])){
					$this->error('分类必须选择【页面返回后请重新选择报修类型】');
				}else{
					$data['cate_fid'] = $_POST['cate'];
				}
			}else{
				$data['cate_id'] = $_POST['subcate'];
				if($_POST['cate']){
					$data['cate_fid'] = $_POST['cate'];
				}
			}
			if(empty($_POST['content'])){
				$this->error('内容不能为空【页面返回后请重新选择报修类型】');
			}
			$data['content'] = $_POST['content'];

			$data['phone'] = $_POST['phone'] ? $_POST['phone']:'';
			$data['uid'] = $_POST['user_bind'] ? $_POST['user_bind']:'';
			$data['user_name'] = $_POST['user_name'] ? $_POST['user_name']:'';
            $data['repair_type'] = $_POST['cate1'];
            if (!$_POST['user_bind_id'] && $data['phone']) {
                $where = [];
                $where['status'] = 1;
                $where['village_id'] = $this->village_id;
                $where['type'] = ['neq',4];
                $where['phone'] = $data['phone'];
                if ($data['uid']) {
                    $where['uid'] = $data['uid'];
                }
                $user_bind = M('House_village_user_bind')->where($where)->order('pigcms_id ASC')->select();
                if ($user_bind) {
                    $owner = reset($user_bind);
                    $data['single_id'] = $owner['single_id'];//楼栋
                    $data['floor_id'] = $owner['floor_id'];  //单元
                    $data['layer_id'] = $owner['layer_id'];  //层
                    $data['vacancy_id'] = $owner['vacancy_id']; //房号
                    $data['bind_id'] = $owner['pigcms_id'];
                    if (!$data['uid'] && $owner['uid']) {
                        $data['uid'] = $owner['uid'];
                    }
                    if (!$data['user_name'] && $owner['name']) {
                        $data['user_name'] = $owner['name'];
                    }
                }
            } else {
                //2020/07/17 bug 改  start
//                $where_owner = [];
//                if (intval($_POST['vacancy_id'])>0) {
//                    $where_owner['vacancy_id']=intval($_POST['vacancy_id']);
//                } else {
//                    $where_owner['phone'] = trim($_POST['phone']);
//                }
//                $where_owner['type'] = ['in', '0,3'];
//                $where_owner['village_id'] = $this->village_id;
//                $owner = M('house_village_user_bind')->where($where_owner)->order('pigcms_id desc')->find();
//                $data['single_id'] = $_POST['single_id'];
//                $data['floor_id'] = $_POST['floor_id'];
//                $data['vacancy_id'] = $_POST['vacancy_id'];
//                $data['bind_id'] = $owner['pigcms_id'];
                //2020/07/17 bug 改  end
                $owner = M('House_village_user_bind')->where(['pigcms_id'=>$_POST['user_bind_id']])->find();
                if ($owner) {
                    $data['single_id'] = $owner['single_id'];//楼栋
                    $data['floor_id'] = $owner['floor_id'];  //单元
                    $data['layer_id'] = $owner['layer_id'];  //层
                    $data['vacancy_id'] = $owner['vacancy_id']; //房号
                    $data['bind_id'] = $owner['pigcms_id'];
                    if (!$data['uid'] && $owner['uid']) {
                        $data['uid'] = $owner['uid'];
                    }
                    if (!$data['phone'] && $owner['phone']) {
                        $data['phone'] = $owner['phone'];
                    }
                    if (!$data['user_name'] && $owner['name']) {
                        $data['user_name'] = $owner['name'];
                    }
                }
            }

			if($_POST['pic']){
				$pic = implode('|',$_POST['pic']);
				$data['pic'] = $pic;
			}

			if(empty($_POST['worker_id'])){
				$this->error('工作人员必须选择!');
			}

			$data['is_read'] = '0';
			$data['status'] = 1;
			$data['type'] = 1;
			$data['time'] = $_SERVER['REQUEST_TIME'];
            $data['classify_id']=$_POST['cate1'];
            $data['repair_type']=D('House_village_repair_classify')->where(['id'=>$data['classify_id']])->getField('type');
			$res = $database_house_village_repair_list->data($data)->add();
			if($res){
				//增加一条用户端数据,为了防止查看不正常状态码为0
				if($data['uid']){
                    $param = [
                        'pageName' => 'waterReportlist',
                        'isAll' => true,
                        'urlParam' => 'type=1&pigcms_id='.$_POST['user_bind_id'].'&village_id='.$this->village_id.'&title='.urlencode('物业报修列表'),
                    ];
                    $waterReportlistUrl = D('House_village')->villagePagePath('','',$param);
//
                    $href = $waterReportlistUrl;

					$user = D('User')->where(array('uid'=>$data['uid']))->find();
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('TM00017',
						array(
							'href' => $href,
							'wecha_id' => $user['openid'],
							'first' => '在线报修',
							'keyword1' => '',
							'keyword2' =>'已发送',
							'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
							'keyword4' => '物业',
							'remark' => '\n物业已为您成功添加一条报修内容,可前去查看！'
						),0,$property_id,2
					);

				}
				D('House_village_repair_log')->add_log(array('status' => 0, 'repair_id' => $res));

				if($_POST['worker_id']){
					$data['worker_id'] = $_POST['worker_id'];
					$worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
					$worker = D('House_worker')->field(true)->where(array('wid' => $worker_id, 'village_id' => $this->village_id))->find();
					if($worker){
						$data['wid'] = $worker_id;
						$data['status'] = 1;
						$where['village_id'] = $this->village_id;
						$where['pigcms_id'] = $res;
						if ($database_house_village_repair_list->where($where)->save($data)) {
							D('House_village_repair_log')->add_log(array('status' => 1, 'repair_id' => $res, 'phone' => $worker['phone'], 'name' => $worker['name']));
						}
					}
				}
				$this->success('添加成功!',U('index'));
			}else{
                fdump($_POST,'$data');
                fdump($data,'$data',1);
                fdump($user_bind,'$data',1);
                fdump($owner,'$data',1);
				$this->error('添加失败!');
			}
		}else{
			//楼栋
			$single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->select();
			if($single_list){
				$this->assign('single_list',$single_list);
			}else{
				$this->assign('single_list',[]);
			}
			$repair_classify=M('House_village_repair_classify')->where(['village_id'=>$this->village_id,'del_time'=>0,'status'=>1])->order('sort desc,id desc')->select();
            $this->assign('classify_list', $repair_classify);
			$workers = D('House_worker')->field(true)->where(array('type' => 1,'status' => 1,'is_del' => 0, 'village_id' => $this->village_id))->select();
			$this->assign('workers', $workers);
			$this->display();
		}
	}

    public function ajax_user_list(){
        if(IS_JAX){
            $find_value = $_POST['find_value'];
            if ($find_value) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            }
            $where['status'] = 1;
            if($_POST['type']){
                $type='true';
            }
            $where['type'] = ['in',[0,1,2,3]];
            $village_id = $this->village_id;
            $where['village_id'] = $village_id;
            $where['single_id'] = ['neq',''];
            $where['uid'] = ['neq',0];
            if (empty($where)) {
                $user_list = D('House_village_user_bind')->get_limit_list_page($village_id);
            } else {
                $user_list['user_list'] = D('House_village_user_bind')->where($where)->limit(15)->select();
            }
            $user_list = $user_list['user_list'];
            foreach ($user_list as &$v)
            {
                $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$this->village_id);
                if (0==$v['type'] || 3==$v['type']) {
                    $v['type_txt'] = '房主';
                } elseif (1==$v['type']) {
                    $v['type_txt'] = '家属';
                } elseif (2==$v['type']) {
                    $v['type_txt'] = '租客';
                }
            }
            if($user_list){
                exit(json_encode(array('status'=>1,'user_list'=>$user_list)));
            }else{
                exit(json_encode(array('status'=>0,'user_list'=>$user_list)));
            }
        }else{
            $this->error_tips('访问页面有误！');
        }
    }

	public function ajax_upload_pic(){
		if ($_FILES['imgFile']['error'] != 4) {
			$img_mer_id = sprintf("%09d", $this->house_session['village_id']);
			$rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

			$upload_dir = './upload/activity/' . $rand_num . '/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
			$upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
			$upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif');
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if ($upload->upload()) {
				$uploadList = $upload->getUploadFileInfo();
				$title = $rand_num . ',' . $uploadList[0]['savename'];
				$slide_image_class = new slide_image();
				$url = $slide_image_class->get_image_by_path($title , 5);
				$title = $rand_num . '/' . $uploadList[0]['savename'];
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			} else {
				exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
			}
		} else {
			exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
		}
	}


	public function ajax_del_pic(){
		$group_image_class = new slide_image();
		$group_image_class->del_image_by_path($_POST['path'],1);
	}


	public function get_single()
	{
		$village_id = $this->house_session['village_id'];
		$phone = $_POST['phone'];
		$user_bind = M('House_village_user_bind')->where(array('phone'=>$phone,'status'=>1,'village_id'=>$village_id, 'type' => array('lt', 4)))->select();
		if(empty($user_bind)){
			echo json_encode(array('status'=>0,'user_bind'=>[]));exit;
		}
		$single_arr = [];
		foreach($user_bind as $k=>$value){
		    if(empty($value['single_id'])){
                $value['single_id'] = M('House_village_user_vacancy')->where(['pigcms_id'=>$value['vacancy_id']])->getField('single_id');
            }
			$single_arr[] = $value['single_id'];
		}
		$where['village_id'] = $this->house_session['village_id'];
		$where['id'] = array('in',$single_arr);
		$single_list = M('House_village_single')->where($where)->select();
		//var_dump(M('House_village_single')->_sql());
		if($single_list){
			echo json_encode(array('status'=>1,'single_list'=>$single_list));exit;
		}else{
			echo json_encode(array('status'=>0,'single_list'=>[]));exit;
		}
	}

	public function ajax_get_user_bind(){
		if(IS_AJAX){
			$village_id = $this->house_session['village_id'];
			$phone = $_POST['phone'];
			$user_bind = M('House_village_user_bind')->where(array('phone'=>$phone,'status'=>1,'village_id'=>$village_id, 'type' => array('lt', 4)))->find();
			if(!empty($user_bind)){

				echo json_encode(array('status'=>1,'user_bind'=>$user_bind));exit;
			}else{
				echo json_encode(array('status'=>0,'user_bind'=>[]));exit;
			}
		}
	}

	public function ajax_get_user_vacancy(){
		if(IS_AJAX){
			$village_id = $this->house_session['village_id'];
			$vacancy_id = $_POST['vacancy_id'];
			$phone = $_POST['phone'];
			$user_bind = M('House_village_user_bind')->where(array('vacancy_id'=>$vacancy_id,'status'=>1,'village_id'=>$village_id, 'type' => array('neq', 4)))->find();
			if(!empty($user_bind)){

				echo json_encode(array('status'=>1,'pigcms_id'=>$user_bind['pigcms_id']));exit;
			}else{
				echo json_encode(array('status'=>0,'$pigcms_id'=>''));exit;
			}
		}
	}

	public function ajax_get_category(){
		$cat_id = $_POST['cat_id'] + 0;
		if(!$cat_id){
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}
        $source = $_POST['source'] ? $_POST['source'] : 'all';
		$database_house_service_category= D('House_village_repair_cate');
		$where['parent_id'] = $cat_id;
		if($source == 'all'){
            $where['status'] = array('neq', 4);
        }else{
            $where['status'] = 1;
        }
		//$where['cat_url'] = array('eq','');
		$cat_list = $database_house_service_category->where($where)->field('id,cate_name')->select();
		if($cat_list){
			exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
		}else{
			exit(json_encode(array('status'=>0,'cat_list'=>$cat_list)));
		}
	}

	public function ajax_get_category1(){
        $village_id = $this->house_session['village_id'];
		$cat_id = $_POST['cat_id'] + 0;
		if(!$cat_id){
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}
        $source = $_POST['source'] ? $_POST['source'] : 'all';
		$database_house_service_category= D('House_village_repair_cate');
		$where['classify_id'] = $cat_id;
		$where['parent_id'] = 0;
        if($source == 'all'){
            $where['status'] = array('neq', 4);
        }else{
            $where['status'] = 1;
        }
        $where['village_id'] = $village_id;
		//$where['cat_url'] = array('eq','');
		$cat_list = $database_house_service_category->where($where)->field('id,cate_name')->select();
		if($cat_list){
			exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
		}else{
			exit(json_encode(array('status'=>0,'cat_list'=>$cat_list)));
		}
	}

	public function ajax_get_floor(){
		$village_id = $this->house_session['village_id'];
		$single_id = $_POST['cat_id'];

		if(!$single_id){
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}

		$phone = $_POST['phone'];
		if($phone){
			$user_bind = M('House_village_user_bind')->where(array('phone'=>$phone,'status'=>1,'village_id'=>$village_id, 'type' => array('neq', 4)))->select();
			if(empty($user_bind)){
				echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
			}
			$floor_arr = [];
			foreach($user_bind as $k=>$value){
				$floor_arr[] = $value['floor_id'];
			}
			$where['floor_id'] = array('in',$floor_arr);
		}


		$database_house_village_floor= D('House_village_floor');
		$where['single_id'] = $single_id;
//		$where['status'] = 1;
		$where['village_id'] = $this->house_session['village_id'];

		$cat_list = $database_house_village_floor->field('floor_id,floor_name')->where($where)->select();
//        var_dump(D('House_village_floor')->_sql());
		if($cat_list){
			exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
		}else{
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}
	}

	public function ajax_get_vacancy(){
		$floor_id = $_POST['cat_id'];
		$village_id = $this->house_session['village_id'];
		if(!$floor_id){
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}
		$phone = $_POST['phone'];
		if($phone){
			$user_bind = M('House_village_user_bind')->where(array('phone'=>$phone,'status'=>1,'village_id'=>$village_id, 'type' => array('neq', 4)))->select();
			if(empty($user_bind)){
				echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
			}
			$floor_arr = [];
			foreach($user_bind as $k=>$value){
				$floor_arr[] = $value['vacancy_id'];
			}

			$where['pigcms_id'] = array('in',$floor_arr);
		}
		$database_house_village_vacancy= D('House_village_user_vacancy');
		$where['layer_id'] = $floor_id;
//		$where['status'] = 1;
		$where['village_id'] = $this->house_session['village_id'];
        $where['is_del'] = 0;

		$cat_list = $database_house_village_vacancy->field('pigcms_id,room')->where($where)->select();
//		var_dump(D('House_village_user_vacancy')->_sql());
		if($cat_list){
			exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
		}else{
			exit(json_encode(array('status'=>0,'cat_list'=>[])));
		}
	}


	public function ajax_get_layer()
	{

        $village_id = $this->house_session['village_id'];
        $floor_id = $_POST['cat_id'];

        if(!$floor_id){
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }

        $phone = $_POST['phone'];
        if($phone){
            $user_bind = M('House_village_user_bind')->where(array('phone'=>$phone,'status'=>1,'village_id'=>$village_id, 'type' => array('neq', 4)))->select();
            if(empty($user_bind)){
                echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
            }
            $layer_arr = [];
            foreach($user_bind as $k=>$value){
                $layer_arr[] = $value['layer_id'];
            }
            $where['id'] = array('in',$layer_arr);
        }
        $database_house_village_layer= D('House_village_layer');
        $where['floor_id'] = $floor_id;
        $where['village_id'] = $this->house_session['village_id'];
        $cat_list = $database_house_village_layer->field('*')->where($where)->select();
      //   print_r($database_house_village_layer->_sql());exit;
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }

		/*$result = M('House_village_layer')->where(array('floor_id'=>$_POST['cat_id']))->select();
		if(!$result){
			exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
		}else{
			exit(json_encode(array('status'=>1,'cat_list'=>$result)));
		}*/
	}

    public function water()
    {
        //水电煤上报-查看 权限
        if (!in_array(222, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$repair_list = D('House_village_repair_list')->getlist(array('village_id' => $this->village_id,'type'=>2));
		if($repair_list['repair_list']){
			foreach($repair_list['repair_list'] as $k=>&$v){
				if(empty($v['address']) || empty($v['usernum'])){
                    $user_bind = D('House_village_user_bind')->where(array('uid'=>$v['uid'],'type'=>['in',[0,3]],'status' => 1,'village_id'=>$v['village_id']))->find();
					$v['address'] = $user_bind['address'];
					$v['usernum'] = $user_bind['usernum'];
                    $v['bind_number'] = $user_bind['bind_number'];
				}
                //根据楼栋id 单元id 楼层id 房号id 获取地址
                $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$this->village_id);

            }
		}

		$this->assign('repair_list', $repair_list);
		$this->display();
    }

	public function add_water()
	{
		if(IS_POST){
			$database_house_village_repair_list = D('House_village_repair_list');

			$data['village_id'] = $this->village_id;
            $property_id = D('House_village')->where(['village_id'=>$this->village_id])->getField('property_id');

			if(empty($_POST['content'])){
				$this->error('内容不能为空');
			}
			$data['content'] = $_POST['content'];

			$data['phone'] = $_POST['phone'] ? $_POST['phone']:'';
			$data['uid'] = $_POST['user_bind'] ? $_POST['user_bind']:'';
			$data['user_name'] = $_POST['user_name'] ? $_POST['user_name']:'';

            //20207/18 bug 改版  start

//			$data['single_id'] = $_POST['single_id'];
//			$data['floor_id'] = $_POST['floor_id'];
//			$data['vacancy_id'] = $_POST['vacancy_id'];
//			$data['bind_id'] = $_POST['vacancy_id'];

            $owner = M('house_village_user_bind')->where(['pigcms_id'=>$_POST['user_bind_id']])->find();
            $data['single_id'] = $owner['single_id'];//楼栋
            $data['floor_id'] = $owner['floor_id'];  //单元
            $data['layer_id'] = $owner['layer_id'];  //层
            $data['vacancy_id'] = $owner['vacancy_id']; //房号
            $data['bind_id'] = $_POST['user_bind_id'];
            //20207/18 bug 改版  end

			if($_POST['pic']){
				$pic = implode('|',$_POST['pic']);
				$data['pic'] = $pic;
			}

			if(empty($_POST['worker_id'])){
				$this->error('工作人员必须选择!');
			}

			$data['is_read'] = '0';
			$data['status'] = 1;
			$data['type'] = 2;
			$data['time'] = $_SERVER['REQUEST_TIME'];
			$res = $database_house_village_repair_list->data($data)->add();
			if($res){
				//增加一条用户端数据,为了防止查看不正常状态码为0
				if($data['uid']){
                    $param = [
                        'pageName' => 'waterReportlist',
                        'isAll' => true,
                        'urlParam' => 'type=2&pigcms_id='.$_POST['user_bind_id'].'&village_id='.$this->village_id.'&title='.urlencode('水电煤上报列表'),
                    ];
                    $waterReportlistUrl = D('House_village')->villagePagePath('','',$param);
					$href = $waterReportlistUrl;
					$user = D('User')->where(array('uid'=>$data['uid']))->find();
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					//$model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $user['openid'], 'first' => '在线报修', 'OrderSn' => '', 'OrderStatus' => '', 'remark' => '物业已为您成功添加一条报修内容,可前去查看'));
					$model->sendTempMsg('OPENTM405462911',
						array(
							'href' => $href,
							'wecha_id' => $user['openid'],
							'first' => '水电煤上报',
							'keyword1' => '',
							'keyword2' =>'已发送',
							'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
							'keyword4' => '物业',
							'remark' => '\n物业已为您成功添加一条水电煤上报内容,可前去查看！'
						),0,$property_id,2
					);
				}
				D('House_village_repair_log')->add_log(array('status' => 0, 'repair_id' => $res));

				if($_POST['worker_id']){
					$data['worker_id'] = $_POST['worker_id'];
					$worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
					$worker = D('House_worker')->field(true)->where(array('wid' => $worker_id, 'village_id' => $this->village_id))->find();
					if($worker){
						$data['wid'] = $worker_id;
						$data['status'] = 1;
						$where['village_id'] = $this->village_id;
						$where['pigcms_id'] = $res;
						if ($database_house_village_repair_list->where($where)->save($data)) {
							D('House_village_repair_log')->add_log(array('status' => 1, 'repair_id' => $res, 'phone' => $worker['phone'], 'name' => $worker['name']));
						}
					}
				}
				$this->success('添加成功!',U('water'));
			}else{
				$this->error('添加失败!');
			}
		}else{
			//楼栋
			$single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->select();
			if($single_list){
				$this->assign('single_list',$single_list);
			}else{
				$this->assign('single_list',[]);
			}

			$workers = D('House_worker')->field(true)->where(array('status' => 1,'is_del' => 0, 'village_id' => $this->village_id))->select();
			$this->assign('workers', $workers);
			$this->display();
		}

	}

    public function suggess(){
    	$village_id = $this->village_id;
    	if($village_id){
    		$repair_list = D('House_village_repair_list')->getlist(array('village_id'=>$village_id,'type'=>3));

    		$this->assign('repair_list',$repair_list);
    	}
    	$this->display();
    }

    public function do_repair(){
    	if(IS_AJAX){
	        //在线报修-标记为已处理 权限
	        if (!in_array(220, $this->house_session['menus']) && !in_array(223, $this->house_session['menus']) ) {
	            $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
	        }

    		$village_id = $this->village_id;
    		$bind_id = $_POST['bind_id']?intval($_POST['bind_id']):0;
    		$cms_id = $_POST['cid']?intval($_POST['cid']):0;
    		if($bind_id && $village_id){
    			$data['village_id'] = $this->village_id;
    			$data['bind_id'] = $bind_id;
    			$data['pigcms_id'] = $cms_id;

    			$result = D('House_village_repair_list')->where($data)->data(array('is_read'=>1))->save();
    			if($result !== false){
    				$this->ajaxReturn(array('error'=>0));
    			}

    			$this->ajaxReturn(array('msg'=>'处理失败请重试','error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'信息有误','error'=>1));
    		}
    		exit;
    	}else{
    		$this->display();
    	}
    }

    public function info()
    {
        //在线报修-查看 权限
        if (!in_array(219, $this->house_session['menus']) && !in_array(222, $this->house_session['menus']) && !in_array(224, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

    	$bind_id = isset($_GET['bindid']) ? intval($_GET['bindid']) : 0;
    	$cms_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    	if ($cms_id) {
    		$condition['bind_id'] = $bind_id;
    		$condition['pigcms_id'] = $cms_id;
    		$condition['village_id'] = $this->village_id;
    		$repair = D('House_village_repair_list')->getlist($condition, 1);
			$repair = $repair['repair_list'][0];
            //根据楼栋id 单元id 楼层id 房号id 获取地址
            $repair['address'] = D('House_village')->get_single_floor_room($repair['single_id'],$repair['floor_id'],$repair['layer_id'],$repair['vacancy_id'],$this->village_id);
			//查询跟进内容
			$follow = array();
			if ($repair['r_status'] >= 2) {
				$follow = D('House_village_repair_follow')->field(true)->where(array('repair_id' => $cms_id))->select();
				if ($follow) {
					foreach ($follow as &$value) {
						$value['time'] = date('Y-m-d H:i:s',$value['time']);
					}
				}
			}
            $arr = array(
                '1'=>'公共维修',
                '2'=>'个人维修'
            );
			if($repair['classify_id']){
                $repair['repair_type'] = D('House_village_repair_classify')->where(['id'=>$repair['classify_id']])->getField('cate_name');
            }else{
                $repair['repair_type'] = $arr[$repair['repair_type']];
            }

            $repair['cate_name']=D('House_village_repair_list')->get_repair_cate($repair);
            /*if($repair['cate_id']){
                $repai_cate = M('House_village_repair_cate')->where(array('id'=>$repair['cate_id']))->find();
                $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$repai_cate['parent_id']))->find();
                if($repai_f_cate && $repai_cate){
                    $repair['cate_name'] = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
                }elseif($repai_f_cate){
                    $repair['cate_name'] = $repai_f_cate['cate_name'];
                }else{
                    $repair['cate_name'] = '-';
                }
            }elseif($repair['cat_fid']){
                $repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$repair['cat_fid']))->find();
                if($repai_f_cate){
                    $repair['cate_name'] = $repai_f_cate['cate_name'];
                }else{
                    $repair['cate_name'] = '-';
                }
            }else{
                $repair['cate_name'] = '-';
            }*/

    		$this->assign('repair', $repair);
    		$this->assign('follow', $follow);
    		if ($repair['r_status']) {
    			$worker = D('House_worker')->field(true)->where(array('wid' => $repair['wid'],'is_del' => 0, 'village_id' => $this->village_id))->find();
    			$this->assign('worker', $worker);
    		} else {
				if ($repair['r_type'] == 1 || $repair['r_type']==4) {
					$type = 1;
					$title = '分发给工作人员(技工)';
					$tip = '请选择维修技工';
				} else {
					$type = 0;
					$title = '分发给工作人员(客服)';
					$tip = '请选择客服专员';
				}
				$msg = array(
					'title' => $title,
					'tip' => $tip,
				);
				$this->assign('msg', $msg);
		    	$workers = D('House_worker')->field(true)->where(array('type' => $type, 'status' => 1,'is_del' => 0, 'village_id' => $this->village_id))->select();
		    	$this->assign('workers', $workers);
    		}
    	}
    	$this->display();
    }


    public function village_suggest()
    {
        //投诉建议-查看 权限
        if (!in_array(224, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['usernum'] = $_GET['find_value'];
					break;
				case 2:
					$where['phone'] = $_GET['find_value'];
					break;
				case 3:
					$where['address'] = $_GET['find_value'];
					break;
				default:
					break;
			}
		}


		$status = $_GET['status'] + 0;

		if($status > 0){
			$where['status'] = $status - 1;
		}

		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['begin_time'] = $begin_time;
			$where['end_time'] = $end_time;
		}elseif(isset($begin_time)){
			$where['begin_time'] = $begin_time;
		}elseif(isset($end_time)){
			$where['end_time'] = $end_time;
		}

		$where['village_id'] = $this->village_id;
		$where['type'] = 3;

		if($_GET['time']){
			$order['time'] = $_GET['time'];
		}
		if ($_GET['cate']) {
			$where['cate_fid'] = $_GET['cate'];
		}

		if ($_GET['subcate']) {
			$where['cate_id'] = $_GET['subcate'];
		}

		$database_house_village_repair_list = D('House_village_repair_list');
		$repair_list = $database_house_village_repair_list->getlist($where , 20 , $order );

		$cate_where = array(
			'village_id'=>$where['village_id'],
			'parent_id'=>0,
			'status'=>1,
			'type'=>3
		);
		$cate_list = M('House_village_repair_cate')->where($cate_where)->getField('id,cate_name');
		$this->assign('cate_list',$cate_list);
		if($repair_list['repair_list']){
			foreach($repair_list['repair_list'] as $k=>&$v){
				if(empty($v['address']) || empty($v['usernum'])){
                    $user_bind = D('House_village_user_bind')->where(array('uid'=>$v['uid'],'type'=>['in',[0,3]],'status' => 1,'village_id'=>$v['village_id']))->find();
                    $v['address'] = $user_bind['address'];
                    $v['usernum'] = $user_bind['usernum'];
                    $v['bind_number'] = $user_bind['bind_number'];
				}

                $v['cate_name']=D('House_village_repair_list')->get_repair_cate($v);

				/*if($v['cate_id']){
					$repai_cate = M('House_village_repair_cate')->where(array('id'=>$v['cate_id']))->find();
					$repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$repai_cate['parent_id']))->find();
					if($repai_f_cate && $repai_cate){
						$v['cate_name'] = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
					}elseif($repai_f_cate){
						$v['cate_name'] = $repai_f_cate['cate_name'];
					}else{
						$v['cate_name'] = '-';
					}
				}elseif($v['cat_fid']){
					$repai_f_cate = M('House_village_repair_cate')->where(array('id'=>$v['cat_fid']))->find();
					if($repai_f_cate){
						$v['cate_name'] = $repai_f_cate['cate_name'];
					}else{
						$v['cate_name'] = '-';
					}
				}else{
					$v['cate_name'] = '-';
				}*/
                //根据楼栋id 单元id 楼层id 房号id 获取地址
                $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$this->village_id);

            }
		}
		$this->assign('repair_list', $repair_list);
		if($begin_time && $end_time){
			unset($where['end_time']);
			unset($where['begin_time']);
			$where['time'] = array('between' , array($begin_time,$end_time));
		}
		$repair_list_count = D('House_village_repair_list')->where($where)->count();
		if($repair_list_count){//全部
			$this->assign('repair_list_count', $repair_list_count);
		}else{
			$this->assign('repair_list_count', 0);
		}

		$repair_count_0 = D('House_village_repair_list')->where($where)->where(array('status'=>0))->count();
		if($repair_count_0){//未指派
			$this->assign('repair_count_0', $repair_count_0);
		}else{
			$this->assign('repair_count_0', 0);
		}

		$repair_count_1 = D('House_village_repair_list')->where($where)->where(array('status'=>1))->count();
		if($repair_count_1){//已指派
			$this->assign('repair_count_1', $repair_count_1);
		}else{
			$this->assign('repair_count_1', 0);
		}

		$repair_count_2 = D('House_village_repair_list')->where($where)->where(array('status'=>2))->count();
		if($repair_count_2){//已受理
			$this->assign('repair_count_2', $repair_count_2);
		}else{
			$this->assign('repair_count_2', 0);
		}

		$repair_count_3 = D('House_village_repair_list')->where($where)->where(array('status'=>3))->count();
		if($repair_count_3){//已处理
			$this->assign('repair_count_3', $repair_count_3);
		}else{
			$this->assign('repair_count_3', 0);
		}

		$repair_count_4 = D('House_village_repair_list')->where($where)->where(array('status'=>4))->count();
		if($repair_count_4){//业主已评价
			$this->assign('repair_count_4', $repair_count_4);
		}else{
			$this->assign('repair_count_4', 0);
		}
		$status_arr=[
            ['key'=>0,'value'=>'全部'],
		    ['key'=>1,'value'=>'未受理'],
            ['key'=>2,'value'=>'物业已受理'],
            ['key'=>3,'value'=>'客服专员已受理'],
            ['key'=>4,'value'=>'客服专员已处理'],
            ['key'=>5,'value'=>'已评价']
        ];
        $this->assign('status_arr', $status_arr);
        $this->display();
    }



	public function add_suggest()
	{
		if(IS_POST){
			$database_house_village_repair_list = D('House_village_repair_list');
			$data['village_id'] = $this->village_id;
            $property_id = D('House_village')->where(['village_id'=>$this->village_id])->getField('property_id');
			if(empty($_POST['subcate'])){

				if(empty($_POST['cate'])){
					$this->error('分类必须选择');
				}else{
					$data['cate_fid'] = $_POST['cate'];
				}
			}else{
				$data['cate_id'] = $_POST['subcate'];
				if($_POST['cate']){
					$data['cate_fid'] = $_POST['cate'];
				}
			}
			if(empty($_POST['content'])){
				$this->error('内容不能为空');
			}
			$data['content'] = $_POST['content'];

			$data['phone'] = $_POST['phone'] ? $_POST['phone']:'';
//			$data['uid'] = $_POST['user_id'] ? $_POST['user_id']:''; //2020/07/18 注释
			$data['user_name'] = $_POST['user_name'] ? $_POST['user_name']:'';

            //20207/18 bug 改版 新增 start
            $data['uid'] = $_POST['user_bind'] ? $_POST['user_bind']:'';
            $owner = M('house_village_user_bind')->where(['pigcms_id'=>$_POST['user_bind_id']])->find();
            $data['single_id'] = $owner['single_id'];//楼栋
            $data['floor_id'] = $owner['floor_id'];  //单元
            $data['layer_id'] = $owner['layer_id'];  //层
            $data['vacancy_id'] = $owner['vacancy_id']; //房号
            $data['bind_id'] = $_POST['user_bind_id'];
            //20207/18 bug 改版 新增  end

			if($_POST['pic']){
				$pic = implode('|',$_POST['pic']);
				$data['pic'] = $pic;
			}

			if(empty($_POST['worker_id'])){
				$this->error('工作人员必须选择!');
			}

			$data['is_read'] = '0';
			$data['status'] = 1;
			$data['type'] = 3;
			$data['time'] = $_SERVER['REQUEST_TIME'];
			$res = $database_house_village_repair_list->data($data)->add();
			if($res){
				//增加一条用户端数据,为了防止查看不正常状态码为0
				if($data['uid']){
                    $param = [
                        'pageName' => 'waterReportlist',
                        'isAll' => true,
                        'urlParam' => 'type=3&pigcms_id='.$_POST['user_bind_id'].'&village_id='.$this->village_id.'&title='.urlencode('投诉建议列表'),
                    ];
                    $waterReportlistUrl = D('House_village')->villagePagePath('','',$param);
					$href = $waterReportlistUrl;

					$user = D('User')->where(array('uid'=>$data['uid']))->find();
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$temp=$model->sendTempMsg('OPENTM405462911',
						array(
							'href' => $href,
							'wecha_id' => $user['openid'],
							'first' => '投诉建议',
							'keyword1' => '',
							'keyword2' =>'已发送',
							'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
							'keyword4' => '物业',
							'remark' => '\n物业已为您成功添加一条投诉建议,可前去查看！'
						),0,$property_id,2
					);
					fdump_api($temp,'suggest',1);
				}
				D('House_village_repair_log')->add_log(array('status' => 0, 'repair_id' => $res));

				if($_POST['worker_id']){
					$data['worker_id'] = $_POST['worker_id'];
					$worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
					$worker = D('House_worker')->field(true)->where(array('wid' => $worker_id, 'village_id' => $this->village_id))->find();
					if($worker){
						$data['wid'] = $worker_id;
						$data['status'] = 1;
						$where['village_id'] = $this->village_id;
						$where['pigcms_id'] = $res;
						if ($database_house_village_repair_list->where($where)->save($data)) {
							D('House_village_repair_log')->add_log(array('status' => 1, 'repair_id' => $res, 'phone' => $worker['phone'], 'name' => $worker['name']));
						}
					}
				}
				$this->success('添加成功!',U('village_suggest'));
			}else{
				$this->error('添加失败!');
			}
		}else{


			$workers = D('House_worker')->field(true)->where(array('type' => 0,'status' => 1,'is_del' => 0, 'village_id' => $this->village_id))->select();
			$this->assign('workers', $workers);

			$cate_where = array(
				'village_id'=>$this->village_id,
				'parent_id'=>0,
				'status'=>1,
				'type'=>3
			);
			$cate_list = M('House_village_repair_cate')->where($cate_where)->getField('id,cate_name');
			$this->assign('cate_list',$cate_list);
			$this->display();
		}
	}


    /**
     * 复制其他小区信息
     * @author:zhubaodi
     * @date_time: 2021/7/23 14:01
     */
    public function copy_village_info(){
        $village_id=  $this->house_session['village_id'];
        $village_copy=$_POST['village_id'];
        $type=$_POST['type'];
        if (empty($village_copy)||empty($village_id)){
            echo json_encode(array('status'=>0,'msg'=>'小区id不能为空！'));exit();
        }
        $house_menu_list=$this->house_menu_list;
        $application=$this->application;
        if (empty($house_menu_list)||empty($application)){
            echo json_encode(array('status'=>0,'msg'=>'当前小区没有该应用，无法复制！'));exit();
        }
        $application_status=0;
        foreach ($house_menu_list as $value){
            if ($type==3&&$value['application_id']==19){
                $application_status=1;
            }
            if ($type==1&&$value['application_id']==16){
                $application_status=1;
            }
        }
        if (empty($application_status)){
            echo json_encode(array('status'=>0,'msg'=>'当前小区没有该应用，无法复制！'));exit();
        }
        $application_status1=0;
        foreach ($application as $value){
            if ($type==3&&$value['application_id']==19){
                $application_status1=1;
            }
            if ($type==1&&$value['application_id']==16){
                $application_status1=1;
            }
        }
        if (empty($application_status1)){
            echo json_encode(array('status'=>0,'msg'=>'当前小区没有该应用，无法复制！'));exit();
        }
        $database_repair_cate = M('House_village_repair_cate');
        $database_repair_classify = M('House_village_repair_classify');
        $where['type'] = $type;
        $where['village_id'] = $village_copy;
        $suggest_cate_list = $database_repair_cate->where($where)->order('parent_id ASC,id ASC')->select();
        //todo 一级分类数据
        $suggest_classify_list = $database_repair_classify->where(['village_id'=>$village_copy,'del_time'=>0])->order('id asc')->select();
        if(empty($suggest_classify_list)){
            echo json_encode(array('status'=>0,'msg'=>'选中的小区暂无数据，无法复制'));exit();
        }
        M()->startTrans();
        //同步删除一级分类
        if($type != 3){
            $database_repair_classify->where(['village_id'=>$village_id,'del_time'=>0])->save(['del_time'=>time()]);
        }
        //同步删除下级分类
        $database_repair_cate->where(array('type'=>$type,'village_id' => $village_id))->delete();
        $save_suggest_cate=[];
        if($type == 3){
            if(!$suggest_cate_list){
                echo json_encode(array('status'=>0,'msg'=>'所选小区投诉建议数据为空，无法复制！'));exit();
            }
            foreach ($suggest_cate_list as $k=>$v){
                if ($v['parent_id']==0){
                    $v['village_id']=$village_id;
                    $save_suggest_cate[$k]['parent_id']=$v['id'];
                    unset($v['id']);
                    $save_suggest_cate[$k]['id'] = $database_repair_cate->add($v);
                    unset($suggest_cate_list[$k]);
                }else{
                    if (isset($save_suggest_cate)&&!empty($save_suggest_cate)){
                        foreach ($save_suggest_cate as $value){
                            if ($value['parent_id']==$v['parent_id']){
                                $parent_id=$value['id'];
                                break;
                            }
                        }
                        if (isset($parent_id)&&!empty($parent_id)){
                            $v['village_id']=$village_id;
                            $v['parent_id']=$parent_id;
                            $save_suggest_cate[$k]['parent_id']=$v['id'];
                            unset($v['id']);
                            $save_suggest_cate[$k]['id'] = $database_repair_cate->add($v);
                            unset($suggest_cate_list[$k]);
                        }
                    }
                }
            }
        }
        else{
            foreach ($suggest_classify_list as $v){
                $d=[
                    'village_id'=>$village_id,
                    'cate_name'=>$v['cate_name'],
                    'sort'=>$v['sort'],
                    'status'=>$v['status'],
                    'add_time'=>time(),
                    'type'=>$v['type']
                ];
                //todo 同步一级分类数据
                $cate_status=$database_repair_classify->add($d);
                if($cate_status){
                    if($suggest_cate_list){
                        foreach ($suggest_cate_list as $k2=>$v2){
                            $d2=[
                                'village_id'=>$village_id,
                                'cate_name'=>$v2['cate_name'],
                                'sort'=>$v2['sort'],
                                'status'=>$v2['status'],
                                'cate_status'=>$v2['cate_status'],
                                'type'=>$v2['type'],
                                'classify_id'=>$cate_status
                            ];
                            if($v['id'] == $v2['classify_id']){
                                if($v2['parent_id'] == 0){
                                    $d2['parent_id']=0;
                                    $save_suggest_cate[$k2]['parent_id']=$v2['id'];
                                    $save_suggest_cate[$k2]['id'] = $database_repair_cate->add($d2);
                                }
                                else{
                                    if($save_suggest_cate){
                                        $parent_id=0;
                                        foreach ($save_suggest_cate as $value){
                                            if ($value['parent_id']==$v2['parent_id']){
                                                $parent_id=$value['id'];
                                                break;
                                            }
                                        }
                                        if($parent_id){
                                            $d2['parent_id']=$parent_id;
                                            $save_suggest_cate[$k2]['parent_id']=$v['id'];
                                            $save_suggest_cate[$k2]['id'] = $database_repair_cate->add($d2);
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
        M()->commit();
        echo json_encode(array('status'=>1,'msg'=>'ok'));exit();

       /* $where1['type'] = $type;
        $where1['village_id'] = $village_id;
        $suggest_cate_list1 = $database_repair_cate->where($where1)->select();
       //  print_r($suggest_cate_list);exit;

        M()->startTrans();
        if (!empty($suggest_cate_list1)) {
            $del_suggest_cate = $database_repair_cate->where(array('village_id' => $village_id))->delete();
        }else{
            $del_suggest_cate=1;
        }
        if (!empty($suggest_cate_list)){
            foreach ($suggest_cate_list as $k=>$v){
                if ($v['parent_id']==0){
                    $v['village_id']=$village_id;
                    $save_suggest_cate[$k]['parent_id']=$v['id'];
                    unset($v['id']);
                    $save_suggest_cate[$k]['id'] = $database_repair_cate->add($v);
                    unset($suggest_cate_list[$k]);
                }else{
                    if (isset($save_suggest_cate)&&!empty($save_suggest_cate)){
                        foreach ($save_suggest_cate as $value){
                            if ($value['parent_id']==$v['parent_id']){
                                $parent_id=$value['id'];
                                break;
                            }
                        }
                        if (isset($parent_id)&&!empty($parent_id)){
                            $v['village_id']=$village_id;
                            $v['parent_id']=$parent_id;
                            $save_suggest_cate[$k]['parent_id']=$v['id'];
                            unset($v['id']);
                            $save_suggest_cate[$k]['id'] = $database_repair_cate->add($v);
                            unset($suggest_cate_list[$k]);
                        }
                    }

                }
            }
        }else{
            $save_suggest_cate=[1];
        }
        if (empty($del_suggest_cate)||empty($save_suggest_cate)){
            M()->rollback();
            echo json_encode(array('status'=>0));
        }else{
            M()->commit();
            echo json_encode(array('status'=>1));
        }*/
    }

	//投诉建议
	public function suggest_cate()
	{
		$database_repair_cate = M('House_village_repair_cate');

		$village_id = $this->house_session['village_id'];

		//$where['cate_status'] = $_GET['cate_status'];
		$where['parent_id'] = 0;
		$where['type'] = 3;
		$where['village_id'] = $village_id;
		//$where['status'] = array('neq',4);
		$list = $database_repair_cate->where($where)->order('sort desc')->select();


		$this->assign('list',$list);
		$this->display();
	}

	public function s_suggest_cate()
	{
		$database_repair_cate = M('House_village_repair_cate');

		$village_id = $this->house_session['village_id'];

		//$where['cate_status'] = $_GET['cate_status'];
		$where['parent_id'] = $_GET['cat_id'];
		$where['type'] = 3;
		$where['village_id'] = $village_id;
		//$where['status'] = array('neq',4);
		$list = $database_repair_cate->where($where)->order('sort desc')->select();


		$this->assign('list',$list);
		$this->display();
	}


    public function ajax_suggest_reply()
    {
        if (IS_AJAX) {
            $pigcms_id = isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
            $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
            $database_house_village_repair_list = D('House_village_repair_list');
            $repair = $database_house_village_repair_list->field(true)->where(array('pigcms_id' => $pigcms_id, 'village_id' => $this->village_id))->find();
            if (empty($repair)) {
                exit(json_encode(array('status' => 0,'msg' => '传递参数有误！')));
            }
            $worker = D('House_worker')->field(true)->where(array('wid' => $worker_id, 'village_id' => $this->village_id))->find();
            if (empty($worker)) {
                exit(json_encode(array('status' => 0, 'msg' => '工作人员不能为空！')));
            }

            $data['wid'] = $worker_id;
            $data['status'] = 1;
            $where['village_id'] = $this->village_id;
            $where['pigcms_id'] = $pigcms_id;
			if ($database_house_village_repair_list->where($where)->save($data)) {
				D('House_village_repair_log')->add_log(array('status' => 1, 'repair_id' => $pigcms_id, 'phone' => $worker['phone'], 'name' => $worker['name']));

				exit(json_encode(array('status'=>1,'msg'=>'提交成功！')));
			} else {
                exit(json_encode(array('status'=>0,'msg'=>'提交失败！')));
            }
        }else{
            $this->error_tips('访问页面有误！请重试~');
        }
    }

    // 废品回收
    public function waste_recovery()
    {
        //废品回收-查看 权限
        if (!in_array(279, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['usernum'] = $_GET['find_value'];
					break;
				case 2:
					$where['phone'] = $_GET['find_value'];
					break;
				case 3:
					$where['address'] = $_GET['find_value'];
					break;
				default:
					break;
			}
		}


		$status = $_GET['status'] + 0;
		if($status > 0){
			$where['status'] = $status - 1;
		}

		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['begin_time'] = $begin_time;
			$where['end_time'] = $end_time;
		}elseif(isset($begin_time)){
			$where['begin_time'] = $begin_time;
		}elseif(isset($end_time)){
			$where['end_time'] = $end_time;
		}

		$where['village_id'] = $this->village_id;
		$where['type'] = 4;

		if($_GET['time']){
			$order['time'] = $_GET['time'];
		}

		$repair_list = D('House_village_repair_list')->getlist($where , 20 , $order);
		//print_r($repair_list);
		$this->assign('repair_list', $repair_list);
		$this->display();
    }


	public function repair_export(){
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['usernum'] = $_GET['find_value'];
					break;
				case 2:
					$where['phone'] = $_GET['find_value'];
					break;
				case 3:
					$where['address'] = $_GET['find_value'];
					break;
				default:
					break;
			}
		}


		$status = $_GET['status'] + 0;
		if($status > 0){
			$where['status'] = $status - 1;
		}

		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['begin_time'] = $begin_time;
			$where['end_time'] = $end_time;
		}elseif(isset($begin_time)){
			$where['begin_time'] = $begin_time;
		}elseif(isset($end_time)){
			$where['end_time'] = $end_time;
		}

		$where['village_id'] = $this->village_id;

		$type = $_GET['type'] + 0;
		$where['type'] = $type;

		$count = D('House_village_repair_list')->getlist($where, -100);

		if($count <= 0 ){
			$this->error('无数据导出！');
		}

		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

		if($type == 1){
	        //在线报修-导出 权限
	        if (!in_array(221, $this->house_session['menus'])) {
	            $this->error('对不起，您没有权限执行此操作');
	        }

			$title = $this->village['village_name'] . '小区-在线报修列表';
		}elseif($type == 3){
	        //投诉列表-导出 权限
	        if (!in_array(225, $this->house_session['menus'])) {
	            $this->error('对不起，您没有权限执行此操作');
	        }

			$title = $this->village['village_name'] . '小区-投诉列表';
		}

		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		$len = ceil($count/1000);
		for ($i = 0; $i < $len; $i++) {
		    $_GET['page'] = $i + 1;
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千个用户');
			$objActSheet = $objExcel->getActiveSheet();

			$objActSheet->setCellValue('A1', '业主编号');
			$objActSheet->setCellValue('B1', '报修人');
			$objActSheet->setCellValue('C1', '联系号码');
			$objActSheet->setCellValue('D1', '状态');
			$objActSheet->setCellValue('E1', '报修内容');
			$objActSheet->setCellValue('F1', '报修时间');
			$objActSheet->setCellValue('G1', '报修地址');
			$objActSheet->setCellValue('H1', '处理人员');
			$objActSheet->setCellValue('I1', '处理人员手机号码');
			$objActSheet->setCellValue('J1', '接单留言');
			$objActSheet->setCellValue('K1', '跟进内容');
			$objActSheet->setCellValue('L1', '回复内容');
			$objActSheet->setCellValue('M1', '回复时间');
			$objActSheet->setCellValue('N1', '评论内容');
			$objActSheet->setCellValue('O1', '评论时间');


			$repair_list = D('House_village_repair_list')->getlist($where,1000);
			$repair_list = $repair_list['repair_list'];
			if (!empty($repair_list)) {
				$index = 2;

				$cell_list = range('A','M');
				foreach ($cell_list as $cell) {
					$objActSheet->getColumnDimension($cell)->setWidth(40);
				}

				if($type == 1){
					foreach ($repair_list as $value) {
						$objExcel->getActiveSheet()->getStyle('K'.$index)->getAlignment()->setWrapText(true);//设置换行
						$objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
                        if ($value['user_name']) {
                            $name = $value['user_name'];
                        } elseif ($value['name']) {
                            $name = $value['name'];
                        } else {
                            $name = '无';
                        }
                        if ($value['repair_phone']) {
                            $phone = $value['repair_phone'];
                        } elseif ($value['phone']) {
                            $phone = $value['phone'];
                        } else {
                            $phone = '无';
                        }
						$objActSheet->setCellValueExplicit('B' . $index, $name);
						$objActSheet->setCellValueExplicit('C' . $index, $phone);

						if($value['r_status'] == 0){
							$status_val = '未指派';
						}elseif($value['r_status'] == 1){
							$status_val = '已指派';
						}elseif($value['r_status'] == 2){
							$status_val = '已受理';
						}elseif($value['r_status'] == 3){
							$status_val = '已处理';
						}elseif($value['r_status'] == 4){
							$status_val = '业主已评价';
						}
						$objActSheet->setCellValueExplicit('D' . $index, $status_val);
						$objActSheet->setCellValueExplicit('E' . $index, $value['content']);
						$objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s',$value['time']));
						$objActSheet->setCellValueExplicit('G' . $index, $value['address']);

						$bind_id = $value['bind_id'];
						$cms_id = $value['pid'];
						if ($bind_id && $cms_id) {
							$condition['bind_id'] = $bind_id;
							$condition['pigcms_id'] = $cms_id;
							$condition['village_id'] = $this->village_id;
							$repair = D('House_village_repair_list')->getlist($condition, 1);
							$repair = $repair['repair_list'][0];

							if ($repair['status']) {
								$worker = D('House_worker')->field(true)->where(array('wid' => $repair['wid'], 'village_id' => $this->village_id))->find();
							}

							$follow_content = '';
							$follow = D('House_village_repair_follow')->field(true)->where(array('repair_id' => $cms_id))->select();
							if ($follow) {
								foreach ($follow as $_follow) {
									$_follow['time'] = date('Y-m-d H:i:s',$_follow['time']);
									$follow_content .= $_follow['time'] .' - '.$_follow['content'] . "\r\n";
								}
							}

                            if ($value['worker_name']) {
                                $worker_name = $value['worker_name'];
                            } else{
                                $worker_name = '无';
                            }
                            if ($value['worker_phone']) {
                                $worker_phone = $value['worker_phone'];
                            } else{
                                $worker_phone = '无';
                            }
							$objActSheet->setCellValueExplicit('H' . $index, $worker_name);
							$objActSheet->setCellValueExplicit('I' . $index, $worker_phone);
							$objActSheet->setCellValueExplicit('J' . $index, $repair['msg']);
							$objActSheet->setCellValueExplicit('K' . $index, $follow_content);
							$objActSheet->setCellValueExplicit('L' . $index, $repair['reply_content']);
							$objActSheet->setCellValueExplicit('M' . $index, $repair['reply_time']>0 ? date('Y-m-d H:i:s',$repair['reply_time']) : "");
							$objActSheet->setCellValueExplicit('N' . $index, $repair['comment']);
							$objActSheet->setCellValueExplicit('O' . $index, $repair['comment_time']>0 ? date('Y-m-d H:i:s',$repair['comment_time']) : "");
						}

						$index++;
					}
				}elseif($type == 3){

					foreach ($repair_list as $value) {
                        if(empty($value['address']) || empty($value['usernum'])){
                            $user_bind = D('House_village_user_bind')->where(array('uid'=>$value['uid'],'village_id'=>$value['village_id']))->find();
                            //$v['address'] = $user_bind['address'];
                            $value['usernum'] = $user_bind['usernum'];
                        }
						$objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
                        if($value['name']){
                            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                        }else{
                            $objActSheet->setCellValueExplicit('B' . $index, $value['user_name']);
                        }
                        if($value['phone']){
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                        }else{
                            $objActSheet->setCellValueExplicit('C' . $index, $value['repair_phone']);
                        }

						if($value['r_status'] == 0){
							$status_val = '未受理';
						}elseif($value['r_status'] == 1){
							$status_val = '物业已受理';
						}elseif($value['r_status'] == 2){
							$status_val = '客服专员已受理';
						}elseif($value['r_status'] == 3){
							$status_val = '客服专员已处理';
						}elseif($value['r_status'] == 4){
							$status_val = '业主已评价';
						}

						$objActSheet->setCellValueExplicit('D' . $index, $status_val);
						$objActSheet->setCellValueExplicit('E' . $index, $value['content']);
						$objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s',$value['time']));
						$objActSheet->setCellValueExplicit('G' . $index, $value['address']);


						$bind_id = $value['bind_id'];
						$cms_id = $value['pid'];
						if ($bind_id && $cms_id) {
							$condition['bind_id'] = $bind_id;
							$condition['pigcms_id'] = $cms_id;
							$condition['village_id'] = $this->village_id;
							$repair = D('House_village_repair_list')->getlist($condition, 1);
							$repair = $repair['repair_list'][0];

							if ($repair['status']) {
								$worker = D('House_worker')->field(true)->where(array('wid' => $repair['wid'], 'village_id' => $this->village_id))->find();
							}

							$follow_content = '';
							$follow = D('House_village_repair_follow')->field(true)->where(array('repair_id' => $cms_id))->select();
							if ($follow) {
								foreach ($follow as $_follow) {
									$_follow['time'] = date('Y-m-d H:i:s',$_follow['time']);
									$follow_content .= $_follow['time'] .' - '.$_follow['content'] . "\r\n";
								}
							}

							$objActSheet->setCellValueExplicit('H' . $index, $worker['name']);
							$objActSheet->setCellValueExplicit('I' . $index, $worker['phone']);
							$objActSheet->setCellValueExplicit('J' . $index, $repair['msg']);
							$objActSheet->setCellValueExplicit('K' . $index, $follow_content);
							$objActSheet->setCellValueExplicit('L' . $index, $repair['reply_content']);
							$objActSheet->setCellValueExplicit('M' . $index, $repair['reply_time']>0 ? date('Y-m-d H:i:s',$repair['reply_time']) : "");
							$objActSheet->setCellValueExplicit('N' . $index, $repair['comment']);
							$objActSheet->setCellValueExplicit('O' . $index, $repair['comment_time']>0 ? date('Y-m-d H:i:s',$repair['comment_time']) : "");
						}

						$index++;
					}

				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();

	}


	public function repair_big_cate()
	{
		/*$list = array(
			array(
				'cat_name' => '公共报修',
				'cat_id'   => '1',
				'cate_status'=>'1'
			),
			array(
				'cat_name' => '个人报修',
				'cat_id'   => '2',
				'cate_status'=>'2'
			),
		);*/
        $where=[
            'village_id'=>$this->house_session['village_id'],
            'del_time'=>0
        ];
        $field='id as cat_id,cate_name as cat_name,id as classify_id,type,status,sort';
        $order='sort desc,id desc';
        $list= D('House_village_repair_classify')->get_repair_classify($where,$field,$order);
		$this->assign('list',$list);
		$this->display();
	}

	//todo 一级分类数据编辑
	public function repair_classify_info(){
        $House_village_repair_classify= D('House_village_repair_classify');
        $village_id = $this->house_session['village_id'];
        if(IS_POST){
            $id = intval($_POST['id']);
            $time=time();
            $parem=[
                'cate_name'=>trim($_POST['cate_name']),
                'sort'=>intval($_POST['sort']),
                'status'=>intval($_POST['status'])
            ];
            if(!empty($id)){
                $parem['update_time']=$time;
                $rr=$House_village_repair_classify->repair_classify_edit(['id'=>$id],$parem);
            }else{
                $parem['village_id']=$village_id;
                $parem['add_time']=$time;
                $rr=$House_village_repair_classify->repair_classify_add($parem);
            }
            if($rr){
                $this->success('操作成功',U('repair_big_cate'));
            }else{
                $this->error('操作失败');
            }
        }
        else{
            $id = intval($_GET['id']);
            if(!empty($id)){
                $where=[
                    'id'=>$id,
                    'village_id'=>$village_id,
                    'del_time'=>0
                ];
                $field='id,cate_name,sort,status';
                $data= $House_village_repair_classify->get_find($where,$field);
            }else{
                $data=[
                    'id'=>0,
                    'cate_name'=>'',
                    'sort'=>0,
                    'status'=>1
                ];
            }
            $this->assign('detail',$data);
            $this->display();
        }
    }

    //todo 一级分类数据删除
    public function repair_classify_del(){
        $id = intval($_GET['id']);
        if(!$id){
            $this->error('传递参数有误！');
        }
        $time=time();
        $village_id = $this->house_session['village_id'];
        $House_village_repair_classify= D('House_village_repair_classify');
        $House_village_repair_cate= D('House_village_repair_cate');
        $repair_cate=$House_village_repair_cate->where(['classify_id'=>$id,'village_id'=>$village_id])->getField('id');
        if($repair_cate){
            $this->error('请先到子分类删除数据');
        }
        $rr=$House_village_repair_classify->repair_classify_edit(['id'=>$id,'village_id'=>$village_id],['update_time'=>$time,'del_time'=>$time]);
        if($rr){
            $this->success('删除成功',U('repair_big_cate'));
        }else{
            $this->error('删除失败');
        }
    }

	public function repair_cate()
	{
		$database_repair_cate = M('House_village_repair_cate');

		$village_id = $this->house_session['village_id'];

		$where['classify_id'] = $_GET['classify_id'];
		$where['parent_id'] = 0;
		$where['type'] = 1;
		$where['village_id'] = $village_id;
		//$where['status'] = array('neq',4);
		$list = $database_repair_cate->where($where)->order('sort desc,id ASC')->select();


		$this->assign('list',$list);
		$this->display();
	}

	public function s_repair_cate()
	{
		$database_repair_cate = M('House_village_repair_cate');

		$village_id = $this->house_session['village_id'];

//		$where['cate_status'] = $_GET['cate_status'];
		$where['parent_id'] = $_GET['cat_id'];
		$where['type'] = 1;
		$where['village_id'] = $village_id;
		//$where['status'] = array('neq',4);
		$list = $database_repair_cate->where($where)->order('sort desc,id ASC')->select();


		$this->assign('list',$list);
		$this->display();
	}

	public function repair_cate_add()
	{
		$cat_id = $_GET['cat_id'] + 0;
        $classify_id = $_GET['classify_id'];
		$village_id = $this->house_session['village_id'];
		if(empty($classify_id)){
			$this->error('缺少参数!');
		}
        $cate_status=D('House_village_repair_classify')->where(['id'=>$classify_id,'village_id'=>$village_id])->getField('type');
		if($cat_id){
			$cat_url = U('s_repair_cate',array('classify_id'=>$classify_id,'cat_id'=>$cat_id));

		}else{
			$cat_url = U('repair_cate',array('classify_id'=>$classify_id));
		}
		if(IS_POST){
            $data['cate_name'] = $_POST['cate_name'];
            $data['parent_id'] = isset($cat_id)?$cat_id:0;
            $data['sort'] = $_POST['sort'];
            $data['status'] = $_POST['status'];
            $data['type'] = 1;
            $data['cate_status'] = $cate_status;
            $data['village_id'] = $village_id;
            $data['classify_id'] = $classify_id;
			$res = M('House_village_repair_cate')->data($data)->add();
			if($res){
				$this->success('数据添加成功' , $cat_url);
			}else{
				$this->error('数据添加失败！');
			}

		}else{
			$this->display();
		}
	}

	public function repair_cate_edit(){
		$id = $_GET['id'] + 0;
        $classify_id = $_GET['classify_id'];
		$cat_id = $_GET['cat_id'] + 0;
		if(!$id){
			$this->error('传递参数');
		}
        if(empty($classify_id)){
            $this->error('缺少参数!');
        }
		if($cat_id){
			$cat_url = U('s_repair_cate',array('cat_id'=>$cat_id,'classify_id'=>$classify_id));
		}else{
			$cat_url = U('repair_cate',array('classify_id'=>$classify_id));
		}
		if(IS_POST){

			$data['cate_name'] = $_POST['cate_name'];
			$data['sort'] = $_POST['sort'];
			$data['status'] = $_POST['status'];

			$result = M('House_village_repair_cate')->where(array('id'=>$id))->data($data)->save();
            $this->success('编辑成功',$cat_url);
		}else{
			$detail = $result = M('House_village_repair_cate')->where(array('id'=>$id))->find();
            $this->assign('detail',$detail);


			$this->display();
		}
	}


	public function repair_cate_del(){
		$id = $_GET['id'] + 0;
		if(!$id){
			$this->error('传递参数有误！');
		}


		$del = M('House_village_repair_cate')->where(array('id'=>$id))->delete();
		if ($del) {
			$f_cate = M('House_village_repair_cate')->where(array('parent_id'=>$id))->select();
			if($f_cate){
				M('House_village_repair_cate')->where(array('parent_id'=>$id))->delete();
			}
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}


	public function suggest_cate_add()
	{
		$cat_id = $_GET['cat_id'] + 0;
		//$cate_status = $_GET['cate_status'];
		$village_id = $this->house_session['village_id'];

		if($cat_id){
			$cat_url = U('s_suggest_cate',array('cat_id'=>$cat_id));

		}else{
			$cat_url = U('suggest_cate');
		}
		if(IS_POST){
			$data['cate_name'] = $_POST['cate_name'];
			$data['parent_id'] = isset($cat_id)?$cat_id:0;
			$data['sort'] = $_POST['sort'];
			$data['status'] = $_POST['status'];
			$data['type'] = 3;
//			$data['cate_status'] = $cate_status;
			$data['village_id'] = $village_id;

			$res = M('House_village_repair_cate')->data($data)->add();
			if($res){
				$this->success('数据添加成功' , $cat_url);
			}else{
				$this->error('数据添加失败！');
			}

		}else{
			$this->display();
		}
	}

	public function suggest_cate_edit(){
		$id = $_GET['id'] + 0;
		//$cate_status = $_GET['cate_status'];
		$cat_id = $_GET['cat_id'] + 0;
		if(!$id){
			$this->error('传递参数');
		}

		if($cat_id){
			$cat_url = U('s_suggest_cate',array('cat_id'=>$cat_id));
		}else{
			$cat_url = U('suggest_cate');
		}
		if(IS_POST){

			$data['cate_name'] = $_POST['cate_name'];
			$data['sort'] = $_POST['sort'];
			$data['status'] = $_POST['status'];

			$result = M('House_village_repair_cate')->where(array('id'=>$id))->data($data)->save();
            $this->success('编辑成功',$cat_url);
		}else{
			$detail = $result = M('House_village_repair_cate')->where(array('id'=>$id))->find();
			$this->assign('detail',$detail);


			$this->display();
		}
	}


	public function suggest_cate_del(){
		$id = $_GET['id'] + 0;
		if(!$id){
			$this->error('传递参数有误！');
		}


		$del = M('House_village_repair_cate')->where(array('id'=>$id))->delete();
		if ($del) {
			$f_cate = M('House_village_repair_cate')->where(array('parent_id'=>$id))->select();
			if($f_cate){
				M('House_village_repair_cate')->where(array('parent_id'=>$id))->delete();
			}
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
}