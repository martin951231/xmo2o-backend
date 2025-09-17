<?php
/*
 * 景区门票
 *   Writers    hanlu
 *   BuildTime  2016/07/04 20:00
 */
class Scenic_ticketAction extends BaseAction{
    # 门票列表
    public function index(){
    	$database_scenic = D('Scenic_ticket');
    	$condition_scenic['scenic_id'] = $this->merchant_session['scenic_id'];
    	$count_store = $database_scenic->where($condition_scenic)->count();
    	$p = new Page($count_store,15);
		$now_ticket = $database_scenic->field(true)->where($condition_scenic)->limit($p->firstRow.','.$p->listRows)->order('ticket_id DESC')->select();
		$ticket_time_array=D('Scenic_ticket')->ticket_time_array;
		foreach ($now_ticket as &$v) {
			$v['auth_count'] = M('Scenic_ticket_token_list')->where(array('ticket_id'=>$v['ticket_id']))->count();
			if($v['is_children']==1){
				$prefix_child = L_('儿童票').'-';
			}else{
				$prefix_child = L_('成人票').'-';
			}
			if($v['ticket_time_type']){
				$prefix_time =$ticket_time_array[$v['ticket_time_type']].'-';
			}

			if($v['ticket_group']==1){
				$prefix_group =L_('单人票').'-';
			}elseif($v['ticket_group']==2){
				$prefix_group =L_('团队票').'-';
			}

			if($v['ticket_union']==1){
				$prefix_union =L_('联票');
			}else{
				$prefix_union=L_('非联票');
			}

			$v['ticket_prefix_title'] = $prefix_child.$prefix_time.$prefix_group.$prefix_union;
		}
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->assign('now_ticket',$now_ticket);
		$this->display();
    }
    # 新增门票
    public function add(){
    	$data	=	array();
    	$data['scenic_id']	=	$this->merchant_session['scenic_id'];
    	$scenic_project	=	M('Scenic_project')->where(array('scenic_id'=>$data['scenic_id']))->find();
    	if(empty($scenic_project)){
			$this->error(L_('请先添加景区项目！'),U('Scenic/project'));
    	}
    	if(IS_POST){
			if(empty($_POST['ticket_title'])){
				$this->error(L_('门票名称必填！'));
			}
            if(empty($_POST['ticket_explain'])){
                $this->error(L_('预定须知必填！'));
            }
            if(empty($_POST['park_intr'])){
				$this->error(L_('入园提示必填！'));
            }
            if(empty($_POST['ticket_cue'])){
				$this->error(L_('入园方式必填！'));
            }
            if(empty($_POST['ticket_price'])){
				$this->error(L_('门票现价必填！'));
            }
            if(empty($_POST['count_num'])){
				$this->error(L_('门票数量必填！'));
            }
            $priceArr = json_decode(htmlspecialchars_decode($_POST['jsonPrice']),true);
			$data['project_id'] = implode(',',$_POST['project']);//项目名
			$data['ticket_title'] = $_POST['ticket_title'];		//门票名称
			$data['ticket_explain'] = $_POST['ticket_explain'];	//预定须知
			$data['park_intr'] = $_POST['park_intr'];			//入园提示
			$data['ticket_cue'] = $_POST['ticket_cue'];			//门票介绍
			if(empty($_POST['old_price'])){
				$data['old_price'] = $_POST['ticket_price'];	//老价格
			}else{
				$data['old_price'] = $_POST['old_price'];		//老价格
			}
			$data['ticket_price'] = $_POST['ticket_price'];		//现价
			$data['serialize'] = serialize($priceArr);			//门票日期序列化
			$data['count_num'] = $_POST['count_num'];			//门票数量
			//$data['start_time'] = isset($_POST['start_time'])?strtotime($_POST['start_time']):'00:00';	//开始时间
//			$data['end_time'] = isset($_POST['end_time'])?strtotime($_POST['end_time']):'00:00';	//结束时间
			$data['start_time'] = $_POST['start_time']==0?'00:00:00':$_POST['start_time'].':00';	//开始时间
			$data['end_time'] = $_POST['end_time']==0?'00:00:00':$_POST['end_time'].':00';	//结束时间
			$data['last_time'] = $_SERVER['REQUEST_TIME'];		//更新时间
			$data['create_time'] = $_SERVER['REQUEST_TIME'];	//创建时间
			$data['ticket_sort'] = $_POST['ticket_sort'];		//排序
//			$data['is_refund'] = $_POST['is_refund'];			//随时退
			$data['is_children'] = $_POST['is_children'];		//门票类型
			$data['is_general'] = $_POST['is_general'];			//节假日
			$data['ticket_status'] = 1;

			$data['ticket_time_type'] = $_POST['ticket_time_type'];	//时间类型
			$data['ticket_custom_time'] = $_POST['ticket_custom_time'];	//自定义时间
			$data['ticket_group'] = $_POST['ticket_group'];	//团队票
			if($data['ticket_time_type']!=1){
				$data['ticket_group'] = 1;
			}
			if( $_POST['ticket_group']==1){
				$data['ticket_union'] = $_POST['ticket_union'];	//联票
				if($_POST['ticket_union']==0){
					$data['use_limit'] = $_POST['use_limit'];	//联票
				}
			}else{
				$data['ticket_union'] = 0;
			}
			$data['ticket_limit'] = $_POST['ticket_limit'];	//最多购买
			$data['ticket_limit_min'] = $_POST['ticket_limit_min'];	//最少购买

			if($data['ticket_limit']<$data['ticket_limit_min'] && $data['ticket_limit']>0){
				$this->error(L_('最多购买数量不能比最少购买数量小'));
			}

			if($_POST['ticket_group']==1 && $_POST['auth_token'] && $_POST['ticket_union']==1){
				foreach ($_POST['auth_token'] as $key=>$v) {
					$where['token'] = $v;
					if($res = D('Scenic_ticket')->get_scenic_one_ticket($where)){
						if($res['scenic_id'] == $this->merchant_session['scenic_id']){
							$this->error(L_('授权码：X1 是同一个景区，无法授权',$v));
						}
						$tmp_ticket_id[] = $res['ticket_id'];
						$res['union_sell'] = $_POST['union_sell'][$key];
						$tmp_scenic_list[$res['ticket_id']] = $res;

					}
				}
				$data['ticket_union_id'] = implode(',',$tmp_ticket_id);
			}

            $add	=	M('Scenic_ticket')->data($data)->add();
            if($add){
				if($tmp_ticket_id){
					foreach ($tmp_ticket_id as $t) {
						$date_token['ticket_id'] = $t;
						$date_token['token'] = $tmp_scenic_list[$t]['token'];
						$date_token['auth_ticket'] = $add;
						$date_token['status'] = 1;
						$date_token['union_sell'] = $tmp_scenic_list[$t]['union_sell'];
						$date_token['add_time'] = time();
						M('Scenic_ticket_token_list')->add($date_token);
					}
				}


                $this->success(L_("添加成功！"));
            }else{
                $this->error(L_("添加失败！"));
            }
		}else{
			$project	=	M('Scenic_project')->where($data)->select();
			$this->assign('project',$project);
			$this->display();
		}
    }

    # 修改门票
    public function edit(){
		$data	=	array();
    	$data['scenic_id']	=	$this->merchant_session['scenic_id'];
    	if(IS_POST){
			if(empty($_POST['ticket_id'])) {
				$this->error(L_('门票ID不能为空！'));
			}else{
				$where['ticket_id']	=	$_POST['ticket_id'];
			}
			if(empty($_POST['ticket_title'])) {
				$this->error(L_('门票名称必填！'));
			}
            if(empty($_POST['ticket_explain'])) {
                $this->error(L_('预定须知必填！'));
            }
            if(empty($_POST['park_intr'])){
				$this->error(L_('入园提示必填！'));
            }
            if(empty($_POST['ticket_cue'])){
				$this->error(L_('入园方式必填！'));
            }
            if(empty($_POST['ticket_price'])){
				$this->error(L_('门票现价必填！'));
            }
            if(empty($_POST['count_num'])){
				$this->error(L_('门票数量必填！'));
            }
            $priceArr = json_decode(htmlspecialchars_decode($_POST['jsonPrice']),true);
			$data['project_id'] = implode(',',$_POST['project']);//项目名
			$data['ticket_title'] = $_POST['ticket_title'];		//门票名称
			$data['ticket_explain'] = $_POST['ticket_explain'];	//预定须知
			$data['park_intr'] = $_POST['park_intr'];			//入园提示
			$data['ticket_cue'] = $_POST['ticket_cue'];			//门票介绍
			if(empty($_POST['old_price'])){
				$data['old_price'] = $_POST['ticket_price'];	//老价格
			}else{
				$data['old_price'] = $_POST['old_price'];		//老价格
			}
			$data['ticket_price'] = $_POST['ticket_price'];		//现价
			$data['serialize'] = serialize($priceArr);			//门票日期序列化
			$data['count_num'] = $_POST['count_num'];			//门票数量
			$data['start_time'] = $_POST['start_time']==0?'00:00:00':$_POST['start_time'].':00';	//开始时间
			$data['end_time'] = $_POST['end_time']==0?'00:00:00':$_POST['end_time'].':00';	//结束时间
			$data['last_time'] = $_SERVER['REQUEST_TIME'];		//更新时间
			$data['ticket_sort'] = $_POST['ticket_sort'];		//排序
//			$data['is_refund'] = $_POST['is_refund'];			//随时退
			$data['is_children'] = $_POST['is_children'];		//门票类型
			$data['is_general'] = $_POST['is_general'];			//节假日
			$data['ticket_status'] = $_POST['ticket_status'];	//状态
			$data['ticket_time_type'] = $_POST['ticket_time_type'];	//时间类型
			$data['ticket_custom_time'] = $_POST['ticket_custom_time'];	//自定义时间
			$data['ticket_group'] = $_POST['ticket_group'];	//团队票
			if($data['ticket_time_type']!=1){
				$data['ticket_group'] = 1;
			}
			if( $_POST['ticket_group']==1){
				$data['ticket_union'] = $_POST['ticket_union'];	//联票
				if($_POST['ticket_union']==0){
					$data['use_limit'] = $_POST['use_limit'];	//联票
				}
			}else{
				$data['ticket_union'] = 0;
			}
			$data['ticket_limit'] = $_POST['ticket_limit'];	//最多购买
			$data['ticket_limit_min'] = $_POST['ticket_limit_min'];	//最少购买

			if($data['ticket_limit']<$data['ticket_limit_min'] && $data['ticket_limit']>0){
				$this->error(L_('最多购买数量不能比最少购买数量小'));
			}

			if($_POST['ticket_group']==1 && $_POST['auth_token'] && $_POST['ticket_union']==1 ){
				foreach ($_POST['auth_token'] as $key=>$v) {
					$condition['token'] = $v;

					if($res = D('Scenic_ticket')->get_scenic_one_ticket($condition)){
						if($res['scenic_id'] == $this->merchant_session['scenic_id']){
							$this->error(L_('授权码：X1 是同一个景区，无法授权',$v));
						}
						$tmp_ticket_id[] = $res['ticket_id'];
						$res['union_sell'] = $_POST['union_sell'][$key];
						$tmp_scenic_list[$res['ticket_id']] = $res;
					}else{
						M('Scenic_ticket_token_list')->where(array('token'=>$v))->delete();
					}


				}

				$data['ticket_union_id'] = implode(',',$tmp_ticket_id);
			}else if( $_POST['ticket_union']==0){
				$data['ticket_union_id'] = '';
			}

            $save	=	M('Scenic_ticket')->where($where)->data($data)->save();
            if($save){
				//授权码更新
				if($_POST['ticket_group']==1 &&  $_POST['auth_token'] && $_POST['ticket_union']==1) {
					//M('Scenic_ticket_token_list')->where(array('auth_ticket' => $_POST['ticket_id']))->delete();
					foreach ($tmp_ticket_id as $t) {
						$date_token['ticket_id'] = $t;
						$date_token['token'] = $tmp_scenic_list[$t]['token'];
						$date_token['auth_ticket'] = $_POST['ticket_id'];
						$date_token['status'] = 1;
						$date_token['union_sell'] = $tmp_scenic_list[$t]['union_sell'];
						$date_token['add_time'] = time();
						$condition_token['auth_ticket'] = $_POST['ticket_id'];
						$condition_token['ticket_id'] = $t;
						if($res = M('Scenic_ticket_token_list')->where($condition_token)->find()){
							M('Scenic_ticket_token_list')->where($condition_token)->save($date_token);
						}else{
							M('Scenic_ticket_token_list')->add($date_token);
						}
					}
				}else{
					$condition_token['auth_ticket'] = $_POST['ticket_id'];
					M('Scenic_ticket_token_list')->where($condition_token)->delete();
				}

                $this->success(L_("修改成功！"));
            }else{
                $this->error(L_("修改失败！"));
            }
		}else{
			$where['ticket_id']	=	$_GET['ticket_id'];
			$ticket	=	M('Scenic_ticket')->field(true)->where($where)->find();
			if(!empty($ticket['ticket_pic'])){
				$store_image_class = new scenic_image();
				$tmp_pic_arr = explode(';',$ticket['ticket_pic']);
				foreach($tmp_pic_arr as $key=>$value){
					$ticket['pic'][$key]['title'] = $value;
					$ticket['pic'][$key]['url'] = $store_image_class->get_image_by_path($value,$this->config['site_url'],'ticket','1');
				}
			}
			if($ticket['serialize']){
				$serialize	=	unserialize($ticket['serialize']);
				$ticket['serialize']	=	json_encode($serialize);
			}else{
				$ticket['serialize']	=	1;
			}
			$this->assign('ticket',$ticket);
			$project	=	M('Scenic_project')->field(true)->where($data)->select();
			$project_id	=	explode(',',$ticket['project_id']);
			foreach($project as $k=>&$v){
				$search	=	in_array($v['project_id'],$project_id);
				if($search){
					$v['is_open']	=	1;
				}else{
					$v['is_open']	=	0;
				}
			}
			$auth_list = D('Scenic_ticket')->get_auth_list_by_ticket_id($_GET['ticket_id']);

			$this->assign('project',$project);
			$this->assign('auth_list',$auth_list);
			$this->display();
		}
    }
    # 删除门票
    public function del(){
    	$where['ticket_id']	=	$_GET['ticket_id'];
    	if(empty($where)){
			$this->error(L_('门票ID不能为空！'));
    	}
    	$ticket	=	M('Scenic_ticket')->where($where)->delete();
		M('Scenic_ticket_token_list')->where(array('auth_ticket'=>$_GET['ticket_id']))->delete();
    	if($ticket){
			$this->success(L_("删除成功！"));
    	}else{
			$this->error(L_("删除失败！"));
    	}
    }
    # 门票订单
    public function order_list(){
		$database_scenic = D('Scenic_order');
    	$where['scenic_id']	=	$condition_scenic['o.scenic_id'] = $this->merchant_session['scenic_id'];
    	$ticket_id = intval($_GET['ticket_id']);
    	if($ticket_id){
			$condition_scenic['t.ticket_id']	=	$ticket_id;
    	}

		if($_GET['is_children']>-1){
			$condition_scenic['t.is_children']	=	$_GET['is_children'];
		}

		if($_GET['is_children']>-1){
			$condition_scenic['t.is_children']	=	$_GET['is_children'];
		}

		if($_GET['ticket_time_type']>-1){
			$condition_scenic['t.ticket_time_type']	=	$_GET['ticket_time_type'];
		}

		if($_GET['ticket_group']>-1){
			$condition_scenic['t.ticket_group']	=	$_GET['ticket_group'];
		}

		if($_GET['ticket_union']>-1){
			$condition_scenic['t.ticket_union']	=	$_GET['ticket_union'];
		}



    	$count_store = $database_scenic->join('as o LEFT JOIN '.C('DB_PREFIX').'scenic_ticket t ON t.ticket_id = o.ticket_id')->where($condition_scenic)->count();
		$p = new Page($count_store,15);
		$now_order = $database_scenic->field('o.*')->join('as o LEFT JOIN '.C('DB_PREFIX').'scenic_ticket t ON t.ticket_id = o.ticket_id')->where($condition_scenic)->limit($p->firstRow.','.$p->listRows)->order('order_id DESC')->select();

		foreach($now_order as &$v){
			$uinfo=D('User')->get_user($v['user_id']);
			$v['truename']=$uinfo['nickname'];
			$v['phone']=$uinfo['phone'];
			$ticket_title	=	M('Scenic_ticket')->field('ticket_title')->where(array('ticket_id'=>$v['ticket_id']))->find();
			$v['ticket_title']	=	$ticket_title['ticket_title'];
			$v['ticket_count']	=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>1))->count();
			$v['park_count']	=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>2))->count();
			$v['guide']			=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>3))->count();
			$v['ticket_num_add']	=	M('Scenic_group_user')->where(array('order_id'=>$v['order_id']))->count();
			$v['sms_count']	=	M('Scenic_group_user')->where(array('order_id'=>$v['order_id'],'send_sms'=>1))->count();
		}
		$pagebar = $p->show();
		# 项目列表
        $ticket_list	=	M('Scenic_ticket')->field(true)->where($where)->select();

        $this->assign('ticket_list', $ticket_list);
		$this->assign('pagebar',$pagebar);
		$this->assign('now_order',$now_order);
		$this->display();
    }
    # 门票订单详情
    public function order_detail(){
    	$condition_scenic['order_id'] = $_GET['order_id'];
    	$database_scenic = D('Scenic_order');
    	$condition_scenic['scenic_id'] = $this->merchant_session['scenic_id'];
        $now_order = $database_scenic->field(true)->where($condition_scenic)->find();
        if (empty($now_order)) {
            exit(L_('此订单不存在！'));
        }
        $uinfo=D('User')->get_user($now_order['user_id']);
		$now_order['truename']=$uinfo['nickname'];
		$now_order['phone']=$uinfo['phone'];
		
        $scenic_ticket	=	D('Scenic_ticket')->get_scenic_one_ticket(array('ticket_id'=>$now_order['ticket_id']));
        $now_order['pay_type'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);
		$com	=	M('Scenic_order_com')->field(true)->where(array('order_id'=>$now_order['order_id']))->select();
		foreach($com as $v){
			if($v['type'] == 1){
				$order_com['ticket'][]	=	$v;
			}else if($v['type'] == 2){
				$order_com['park'][]	=	$v;
			}else if($v['type'] == 3){
				$order_com['guide'][]	=	$v;
			}
		}
		if($order_com['guide']){
			$guide_name	=	M('Scenic_guide')->field('guide_name')->where(array('guide_id'=>$order_com['guide'][0]['type_id']))->find();
			$order_com['guide'][0]['guide_name']	=	$guide_name['guide_name'];
		}
		if($now_order['family_id']){
			$family_id = explode(",",$now_order['family_id']);
			foreach($family_id as $v){
				$scenic_family[]	=	M('Scenic_family')->field(true)->where(array('family_id'=>$v))->find();
			}
		}
		//$a_img = strstr($now_order['group_traveler_img'], ',',true);

		if($now_order['is_group']==2){
			//$store_image_class = new scenic_image();
			$now_order['group_traveler_img'] && $now_order['group_traveler_img'] = explode(';',$now_order['group_traveler_img']);
			$user_list = M('Scenic_group_user')->where(array('order_id'=>$now_order['order_id']))->select();
			$now_order['group_traveler'] = $user_list;
		}

        $this->assign('scenic_family', $scenic_family);
        $this->assign('now_order', $now_order);
        $this->assign('order_com', $order_com);
        $this->assign('scenic_ticket', $scenic_ticket);
        $this->display();
    }
	# 上传图片
	public function ajax_upload_pic(){
		if($_FILES['imgFile']['error'] != 4){
			$param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $this->config['group_pic_width'];
            $param['thumbMaxHeight'] = $this->config['group_pic_height'];
            $param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'scenic/ticket',1,$param);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$title = $image['title']['imgFile'];
				$merchant_image_class = new scenic_image();
				$url = $merchant_image_class->get_image_by_path($title,$this->config['site_url'],'ticket','-1');
				exit(json_encode(array('error' => 0, 'url' => $url['image'], 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}
	# 删除图片
	public function ajax_del_pic(){
		$merchant_image_class = new scenic_image();
		$merchant_image_class->del_image_by_path($_POST['path']);
	}

	public function  verify(){
		$find_value = $_POST['find_value'];
		$find_type = $_POST['find_type'];

		if($find_type == 1 && strlen($find_value) == 14){
			$res = D('Scenic_order_com')->where(array('code'=>array('like','%'.$find_value.'%')))->find();
			$condition_scenic['order_id']=$res['order_id'];
		}else{
			if($find_type == 1){
				$res = D('Scenic_order_com')->where(array('code'=>array('like','%'.$find_value.'%')))->find();
				$condition_scenic['order_id']=$res['order_id'];
			}else if($find_type == 2){
				$condition_scenic['order_id'] = $find_value;
			}else if($find_type == 3){
				$condition_scenic['user_id']=$find_value;
			}else if($find_type == 4){
				$user = M('User')->where(array('nickname'=>array('like','%'.$find_value.'%')))->find();
				if($user){
					$condition_scenic['user_id'] = $user['uid'];
				}
			}else if($find_type == 5){
				$now_user = D('User')->get_user($find_value,'phone');
				if(empty($now_user)){
					$this->error(L_('该手机未在平台注册为会员'));
				}
				$condition_scenic['user_id'] = $now_user['uid'];
			}
		}
		$this->assign('find_type',$find_type);
		$this->assign('find_value',$find_value);
		$database_scenic = D('Scenic_order');
		$where['scenic_id']	=	$condition_scenic['scenic_id'] = $this->merchant_session['scenic_id'];
		$condition_scenic['order_status']=1;

		$condition_scenic['ticket_time']=array('like','%'.date('Y-n-j').'%');
		$ticket_id = intval($_GET['ticket_id']);
		if($ticket_id){
			$condition_scenic['ticket_id']	=	$ticket_id;
		}
		$count_store = $database_scenic->where($condition_scenic)->count();
		$p = new Page($count_store,15);
		$now_order = $database_scenic->field(true)->where($condition_scenic)->limit($p->firstRow.','.$p->listRows)->order('order_id DESC')->select();
		foreach($now_order as &$v){
			$user	=	D('User')->get_user($v['user_id']);
			$v['nickname']	=	$user['nickname'];
			$v['phone']	=	$user['phone'];
			$ticket_title	=	M('Scenic_ticket')->field('ticket_title')->where(array('ticket_id'=>$v['ticket_id']))->find();
			$v['ticket_title']	=	$ticket_title['ticket_title'];
			//购买门票数量
			$v['ticket_count']	=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>1))->count();
			//停车位数量
			$v['park_count']	=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>2))->count();
			//导游数量
			$v['guide']			=	M('Scenic_order_com')->where(array('order_id'=>$v['order_id'],'type'=>3))->count();
		}
		$pagebar = $p->show();
		# 项目列表
		$ticket_list	=	M('Scenic_ticket')->field(true)->where($where)->select();
		$this->assign('ticket_list', $ticket_list);
		$this->assign('pagebar',$pagebar);
		$this->assign('now_order',$now_order);
		$this->display();
	}

	//景区门票多核销券
	public function ticket_pass_array(){
		$order_id=I('order_id','','intval');
		if(empty($order_id)){
			$this->error(L_('缺少参数！'));	
		}
		$where=array();
		$where['order_id']=$order_id;
		$info=M('scenic_order')->where($where)->field('order_id,order_imm,paid,order_status')->find();
		$where['type']=1;
		$ticket_list=M('Scenic_order_com')->where($where)->field('com_id,type_id,code,status')->select();
		$where['status']=2;
		$use_num=M('Scenic_order_com')->where($where)->count(1);
		if($info['order_status']==1 && $use_num==0){
			$info['is_verify']=0;
		}else{
			$info['is_verify']=1;
		}
		//获取门票标题
		$ticket_title=M('Scenic_ticket')->where(array('ticket_id'=>$ticket_list['0']['type_id']))->getField('ticket_title');
		$info['ticket_title']=$ticket_title;
		$this->assign('info',$info);
		$this->assign('ticket_list',$ticket_list);
		$this->display();
	}

	//景区门票核销
	public function ajax_verify_ticket(){
		$order_id=I('order_id','','intval');
		$result_arr=array();
		$result_arr['errorCode']=1;
		if(empty($order_id)){
			$result_arr['errorMsg']=L_('缺少参数！');
			echo json_encode($result_arr);exit;
		}
		$where=array();
		$where['order_id']=$order_id;
		$info=D("Scenic_order")->get_one_order($where);
		if(empty($info)){
			$result_arr['errorMsg']=L_('订单不存在！');
			echo json_encode($result_arr);exit;
		}elseif($info['paid']!=2 || $info['order_status']!=1){
			$result_arr['errorMsg']=L_('订单状态错误！');
			echo json_encode($result_arr);exit;
		}
		//卡券id
		$com_id=I('com_id',0,'intval');
		$db=M('Scenic_order_com');
		//核销指定票券
		if($com_id>0){
			//该订单所有的票券
			$where2=array();
			$where2['order_id']=$order_id;
			$where2['type']=1;
			$card_info=$db->where($where2)->field("count(*) as count,type_id")->find();
			$count=$card_info['count'];
			//已经核销的票券数量
			$where2['status']=2;
			$done_num=$db->where($where2)->count(1);
			if($count<=$done_num){
				$result_arr['errorMsg']=L_('卡券已全部核销！');
				echo json_encode($result_arr);exit;
			}
			$where2['com_id']=$com_id;
			$where2['status']=1;	//未核销
			$ticket_info=$db->where($where2)->find();
			if(empty($ticket_info)){
				$result_arr['errorMsg']=L_('卡券不存在！');
				echo json_encode($result_arr);exit;
			}
			$time=time();
			//更新票券的状态和记录已使用次数
			$data=array();
			$data['status']=2;
			$data['last_time']=$time;
			$use_limit=M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->getField('use_limit');
            if($use_limit>0){
                $data['use_num']=$use_limit;
            }
			$res=$db->where($where2)->save($data);
			if($res){
				//更新景区收入
                $desc=L_('核销景区门票订单X1的消费码',$info['order_imm']).'<br/>'.$ticket_info['code'].L_('记入收入');
                $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$ticket_info['price'],$desc,$order_id);
                //全部完成处理
                if($count-$done_num==1){    
                    D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
                    //增加票券的销售数量
                    M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$count);
                    //增加导游服务人数
                    $where3=array();
                    $where3['order_id']=$order_id;
                    $where3['type']=3;
                    $guide_id=$db->where($where3)->getField('type_id');
                    if($guide_id>0){
                        M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);   
                    }
                    $where4=array();
                    $where4['order_id']=$order_id;
                    $where4['type']=array('in',array(2,3));
                    $data2=array();
                    $data2['status']=2;
					$data2['last_time']=$time;
                    M('Scenic_order_com')->where($where4)->save($data2);
                    $total_price=M('Scenic_order_com')->where($where4)->sum('price');
                    if($total_price>0){
                        $desc=L_('核销景区门票订单X1的导游、停车场收费记入收入',$info['order_imm']);
                        $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$total_price,$desc,$order_id);
                    }
                }
				$result_arr['errorCode']=0;
				$result_arr['errorMsg']=L_('操作成功！');
				echo json_encode($result_arr);exit;
			}else{
				$result_arr['errorMsg']=L_('操作失败，请重试！');
				echo json_encode($result_arr);exit;
			}			
		}else{
			//更新停车场和导游的信息
			$data=array();
			$data['status']=2;
			$data['last_time']=time();
			$where['type']=array('in',array(2,3));
			$res1=$db->where($where)->save($data);
			//更新票券的信息
            if($info['is_group']==2){
                $num=$info['ticket_num'];
                $data['use_num']=$num;
            }else{
                $type_id=$db->where(array('order_id'=>$order_id,'type'=>1))->getField('type_id');
                $use_limit=M('scenic_ticket')->where(array('ticket_id'=>$type_id))->getField('use_limit');
                if($use_limit>0){
                    $data['use_num']=$use_limit;
                }
            }
			$where['type']=1;
			$res2=$db->where($where)->save($data);
			if($res2){
				$ticket_info=$db->where($where)->field('count(*) as count,type_id')->find();
				D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
				//增加票券的销售数量
				M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$ticket_info['count']);
				//增加导游服务人数
				$where3=array();
				$where3['order_id']=$order_id;
				$where3['type']=3;
				$guide_id=$db->where($where3)->getField('type_id');
				M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);
				//更新景区收入
				$res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$info['order_total'],L_('用户购买门票记入收入'),$order_id);
				$result_arr['errorCode']=0;
				$result_arr['errorMsg']=L_('操作成功！');
				echo json_encode($result_arr);exit;
			}else{
				$result_arr['errorMsg']=L_('操作失败，请重试！');
				echo json_encode($result_arr);exit;
			}
		}
	}

	public function create_token(){
		$ticket_id = $_GET['ticket_id'];
		if($_GET['ac'] =='create'){
			$token = md5(md5($ticket_id.time()));
			M('Scenic_ticket')->where(array('ticket_id'=>$ticket_id))->setField('token',$token);
		} else{
			$token = $_GET['token'];
		}


		$this->assign('token',$token);
		$this->display();
	}

	public function token_list(){
		$where['ticket_id'] = $_GET['ticket_id'];
		$where['status'] = 1;

		$auth_list = D('Scenic_ticket')->get_authed_list_by_ticket_id($_GET['ticket_id']);

		$auth_status_txt = array(
			1=>L_('正常'),
			2=>L_('已失效'),
			3=>L_('门票关闭'),
			4=>L_('过期'),
		);
		$this->assign('status_txt',$auth_status_txt);
		$this->assign('list',$auth_list);
		$this->display();
	}

	public function token_search(){
		$where['token'] = $_POST['token'];
		if($res = D('Scenic_ticket')->get_scenic_one_ticket($where)){
			if($res['scenic_id'] == $this->merchant_session['scenic_id']){

				echo json_encode(array('errorCode'=>1,'errorMsg'=>L_('授权码：X1 是同一个景区，无法授权',$_POST['token'])));exit;
			}
			echo json_encode(array('errorCode'=>0,'ticket'=>$res));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'errorMsg'=>L_('门票不存在！')));exit;
		}
	}

	public function union_sell_status(){
		$ticket_id = $_POST['ticket_id'];
		$union_sell = $_POST['union_sell']?0:1;
		$token = $_POST['token'];
		$where['auth_ticket'] = $ticket_id;
		$where['token'] = $token;
		if(M('Scenic_ticket_token_list')->where($where)->setField('union_sell',$union_sell)){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('更新成功'),'status'=>$union_sell));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'ms'=>L_('更新失败')));exit;
		}

	}

	public function unlink(){
		$ticket_id = $_POST['ticket_id'];
		$token = $_POST['token'];
		$where['auth_ticket'] = $ticket_id;
		$where['token'] = $token;
		if(M('Scenic_ticket_token_list')->where($where)->delete()){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('删除成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'ms'=>L_('删除失败')));exit;
		}
	}


	public function save_traveler(){
		$order_id = $_POST['order_id'];
		$person_id = $_POST['person_id'];

		$person_name = $_POST['person_name'];
		$phone = $_POST['phone'];
		if(empty($person_name)){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('姓名必填')));exit;
		}
		if(empty($person_id)){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('身份证必填')));exit;
		}
		if(empty($phone)){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('手机号码必填')));exit;
		}
		$where['order_id'] = $order_id;
		$where['person_id'] = $person_id;
		$where['person_name'] = $person_name;

		$data['order_id']  =$order_id;
		$data['person_name']  =$person_name;
		$data['phone']  =$phone;
		$data['person_id']  =$person_id;
		$data['status']  =0;
		$data['use_num']  =0;
		$data['order_id']  =$order_id;
		$edit =0;
		if(M('Scenic_group_user')->where($where)->find()){
			$result = M('Scenic_group_user')->where($where)->save($data);
			$edit = 1;
		}else{
			$result = M('Scenic_group_user')->add($data);
		}

		if($result){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('编辑成功'),'edit'=>$edit));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('编辑失败'),'edit'=>$edit));exit;
		}
	}

	public  function send_sms(){
		$order_id = $_POST['order_id'];
		$phone = $_POST['phone'];
		$where['order_id'] = $order_id;
		$now_order = D('Scenic_order')->get_one_order($where);
		$condition['phone'] = $phone;
		$condition['order_id'] = $order_id;
		$now_traveler = D('Scenic_ticket')->get_group_traveler($condition);
		if($now_traveler['send_sms']){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('已发送过了')));exit;
		}else{
			$now_scenic = D('Scenic_ticket')->get_scenic_one_ticket(array('ticket_id'=>$now_order['ticket_id']));
			$href = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
			$modes = D('Access_token_expires');
			$token = $modes->get_access_token();

			$short_url_now_user = long2short_url($token['access_token'],$href.'&order_token='.$now_order['token']);

			$sms_data = array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => 0, 'type' => 'ticket');
			$sms_data['uid'] = $phone;
			$sms_data['mobile'] = $phone;
			$sms_data['sendto'] = 'ticket_group_user';
			$desc_txt = $now_order['ticket_time'].' '.$now_scenic['scenic_title'];

			$sms_data['content'] = "您购买{$desc_txt}景区门票成功，消费码{$now_order['code']}。入园请戳以下链接:{$short_url_now_user} 获取二维码，凭二维码过闸机入园。建议手机截图本二维码保存，毋转发他人使用";
			$res = Sms::sendSms($sms_data);
			D('Scenic_group_user')->where($condition)->setField('send_sms',1);
			echo json_encode(array('errorCode'=>0,'msg'=>L_('发送成功')));exit;
		}

	}

	public function change_order_price(){
		$order_id = $_POST['order_id'];
		$price = $_POST['price'];
		$where['order_id'] = $order_id;
		$res = D('Scenic_order')->get_one_order($where);
		if($res['paid']==2){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('订单已支付，不能修改')));exit;
		}
		if($price<0){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('金额错误')));exit;
		}
		if(M('Scenic_order')->where($where)->setField('order_total',$price)){
			echo json_encode(array('errorCode'=>1,'msg'=>L_('修改成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('修改失败')));exit;
		}
	}
}