<?php
/*
 * 小区新闻
 *
 */
class NewsAction extends BaseAction{
	protected $village_id;
	protected $village;

	public function _initialize(){
		parent::_initialize();

		$this->village_id = $this->house_session['village_id'];
		$this->village = D('House_village')->where(array('village_id'=>$this->village_id))->find();
		if(empty($this->village)){
			$this->error('该'.C('config.house_name').'不存在！');
		}
		if($this->village['status'] == 0){
			$this->assign('jumpUrl',U('Index/config'));
			$this->error('您需要先完善信息才能继续操作');
		}
	}

	// 所有新闻列表
	public function index(){
        //新闻列表-查看 权限
        if (!in_array(158, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$condition['village_id'] = $this->village_id;
		$news_list = D('House_village_news')->getlist($condition);

		$this->assign('news_list',$news_list['news_list']);
		$this->assign('pagebar',$news_list['pagebar']);
		$this->display();
	}

	public function news_edit(){
		$news_id = $_GET['news_id'];

		// 所有新闻的分类
		$condition['village_id'] = $this->village_id;
		$condition['cat_status'] = 1;
		$news_categorys = D('House_village_news_category')->order('cat_sort DESC')->where($condition)->select();

		if(count($news_categorys) < 1){
			$this->error('您还未添加新闻分类，请先去添加新闻分类',U('News/cate_edit'));
		}
		$this->assign('news_categorys',$news_categorys);
		if($news_id){
			$condition_village['village_id'] = $this->village_id;
			$condition_village['news_id'] = $news_id;
			//$condition_village['status'] = 1;
			$news_info = D('House_village_news')->where($condition_village)->find();
			if(empty($news_info)){
				$this->error('暂无此新闻');
			}
            $news_info['end_time']=empty($news_info['end_time']) ? '' : date('Y-m-d H:i:s',$news_info['end_time']);
			$this->assign('news_info',$news_info);
			if (!$news_info['floor_txt']) {
                $news_info['floor_txt'] = '';
            }
            $this->assign('selecteds',$news_info['floor_txt']);
//			if ($news_info['floor_txt']) {
//                $floor_txt_arr = explode(',', $news_info['floor_txt']);
//                $where_choose = array();
//                $where_choose['floor_id'] = array('in', $floor_txt_arr);
//                $database_house_village_floor = D('House_village_floor');
//                $choose_data = $database_house_village_floor->where($where_choose)->field('floor_id, floor_layer,single_id, floor_name')->select();
//                // 直接取所有该小区楼栋信息
//                $database_house_village_single = D('House_village_single');
//                $where_single['status'] = 1;
//                $where_single['village_id'] = $this->house_session['village_id'];
//                $single_list = $database_house_village_single->where($where_single)->getField('id, single_name');
//                if ($choose_data && $single_list) {
//                    foreach($choose_data as &$val) {
//                        if ($val['single_id'] && $single_list[$val['single_id']] && !$val['floor_layer']) {
//                            $val['floor_layer'] = $single_list[$val['single_id']];
//                        }
//                    }
//                }
//                $this->assign('choose_data',$choose_data);
//            }
		}

		$this->display();
	}

    /**
     * 选择对象
     * @author liukezhu
     * @date 2021/4/12
     */
	public function choice_object(){

        //楼栋
        $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort desc, id asc')->select();
        $this->assign('village_id',$this->village_id);
        if($single_list){
            $this->assign('single_list',$single_list);
        }else{
            $this->assign('single_list',[]);
        }
        if($_GET['vacancy_id']){
            $this->assign('vacancy_id',$_GET['vacancy_id']);
        }else{
            $this->assign('vacancy_id','');
        }

        $where=array();$_string=[];
        if($_GET['single_id'] !=0){
            $where['r.single_id'] = $_GET['single_id'];
        }
        if($_GET['floor_id'] !=0){
            $where['r.floor_id'] = $_GET['floor_id'];
        }
        if($_GET['layer_id'] !=0){
            $where['r.layer_id'] = $_GET['layer_id'];
        }
        if($_GET['vacancy_id'] !=0){
            $where['n.room_id'] = $_GET['vacancy_id'];
        }

        //存在文章id
        if($_GET['news_id']){
            $where['n.news_id']=$_GET['news_id'];
        }else{
            $where['n.news_id']=0;
        }

        /**
         * todo 查询该小区下所有单元房间
         */
        $village_floor_list = D('House_village_news_notice')->choose_bind_list($this->village_id,$where,10);
        // 直接取所有该小区楼栋信息
        $database_house_village_single = D('House_village_single');
        $where_single['status'] = 1;
        $where_single['village_id'] = $this->house_session['village_id'];
        $single_list = $database_house_village_single->where($where_single)->getField('id, single_name');
        if ($village_floor_list['list'] && $single_list) {
            foreach($village_floor_list['list'] as &$val) {
                if ($val['single_id'] && $single_list[$val['single_id']] && !$val['floor_layer']) {
                    $val['floor_layer'] = $single_list[$val['single_id']];
                }
            }
        }
        $this->assign('list',$village_floor_list['list']);
        $this->assign('pagebar',$village_floor_list['pagebar']);

        $this->display();
    }


    /**
     * 选择移除
     * @author liukezhu
     * @date 2021/4/13
     */
    public function choice_remove(){
        if(IS_POST){
            if(!isset($_POST['notice_id']) || empty($_POST['notice_id'])){
                $this->error('缺少要参数');
            }
            $notice_id=intval($_POST['notice_id']);
            if(empty($notice_id)){
                $this->error('数据为空');
            }
            $rel = D('House_village_news_notice')->choose_remove($this->village_id,$notice_id);
            if(!$rel){
                $this->error('操作失败');
            }else{
                $this->success(L_("移除成功！"));
            }
        }
    }

	public function floor_list() {
        if(IS_POST){
            $bind_type=isset($_POST['bind_type']) ? trim($_POST['bind_type']):'';
            $news_id=isset($_POST['news_id']) ? intval($_POST['news_id']):0;
            if($bind_type=='search'){
                $_string=[];
                $where = array(
                    'r.village_id'=>$this->village_id,
                    'r.is_del'=>0,
                    'r.status'=>array('in','1,3')
                );
                if(isset($_POST['single_id']) && $_POST['single_id']){
                    $where['r.single_id'] = intval($_POST['single_id']);
                }
                if(isset($_POST['floor_id']) && $_POST['floor_id']){
                    $where['r.floor_id'] = $_POST['floor_id'];
                }
                if(isset($_POST['layer_id']) && $_POST['layer_id']){
                    $where['r.layer_id'] = $_POST['layer_id'];
                }
                if(isset($_POST['vacancy_id']) && $_POST['vacancy_id']){
                    $_string[]=' r.pigcms_id = '.$_POST['vacancy_id'].' ';
                }
                
                //存在文章id
                if($news_id){
                    $room_id = D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>$news_id])->getField('room_id',true);
                }else{
                    $room_id = D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>0])->getField('room_id',true);
                }
                if($room_id){
                    $_string[]=' r.pigcms_id not in ('.implode(',',$room_id).') ';
                }
                if(!empty($_string)){
                    $where['_string']=implode('and',$_string);
                }

                $list = M('House_village_user_vacancy as r')
                    ->where($where)
                    ->field('r.village_id,r.pigcms_id,r.floor_id, r.single_id,r.layer_id')->order('r.add_time asc')
                    ->select();
                if($list){
                    foreach ($list as $vv){
                        $mark_id=$vv['village_id'].'-'.$vv['single_id'].'-'.$vv['floor_id'].'-'.$vv['layer_id'].'-'.$vv['pigcms_id'];
                        $mark_id_arr=array();
                        $mark_id_arr[]=$mark_id;
                        $rel = D('House_village_news_notice')->choose_floor_add($this->village_id,$mark_id_arr,$news_id);
                    }
                    $this->success(L_("绑定成功！"));
                }
                $this->error('操作失败');
            }else{
                if(!isset($_POST['mark_id']) || empty($_POST['mark_id'])){
                    $this->error('缺少要参数');
                }
                $mark_id=explode(',',$_POST['mark_id']);
                if(empty($mark_id)){
                    $this->error('数据为空');
                }
                $rel = D('House_village_news_notice')->choose_floor_add($this->village_id,$mark_id,$_POST['news_id']);
                if(!$rel){
                    $this->error('操作失败');
                }else{
                    $this->success(L_("绑定成功！"));
                }
            }

        }else{
            import('@.ORG.merchant_page');
//        $where = array();
//        $where['village_id'] = $this->village_id;
//        $where['status'] = 1;
//        $count = D('House_village_floor')->where($where)->count();
//        $p = new Page($count,5,'page');
//        $village_floor_list = D('House_village_floor')->where($where)->field('floor_id, single_id, floor_layer, floor_name')->order('floor_id desc')->limit($p->firstRow.','.$p->listRows)->select();

            $_string=[];
            $where=array();
            if($_GET['single_id'] !=0){
                $where['r.single_id'] = $_GET['single_id'];
            }
            if($_GET['floor_id'] !=0){
                $where['r.floor_id'] = $_GET['floor_id'];
            }
            if($_GET['layer_id'] !=0){
                $where['r.layer_id'] = $_GET['layer_id'];
            }
            if($_GET['vacancy_id'] !=0){
                $_string[]=' r.pigcms_id = '.$_GET['vacancy_id'].' ';
//                $where['r.pigcms_id'] = $_GET['vacancy_id'];
            }
            //存在文章id
            if($_GET['news_id']){
                $room_id = D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>$_GET['news_id']])->getField('room_id',true);
            }else{
                $room_id = D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>0])->getField('room_id',true);
            }
            if($room_id){
                $_string[]=' r.pigcms_id not in ('.implode(',',$room_id).') ';
//                $where['r.pigcms_id']=array('not in',implode(',',$room_id));
            }
            if(!empty($_string)){
                $where['_string']=implode('and',$_string);
            }

            /**
             * todo 查询该小区下所有单元房间
             */
            $village_floor_list = D('House_village_news_notice')->choose_floor_list($this->village_id,$where,10);

            // 直接取所有该小区楼栋信息
            $database_house_village_single = D('House_village_single');
            $where_single['status'] = 1;
            $where_single['village_id'] = $this->house_session['village_id'];
            $single_list = $database_house_village_single->where($where_single)->getField('id, single_name');
            if ($village_floor_list['list'] && $single_list) {
                foreach($village_floor_list['list'] as &$val) {
                    if ($val['single_id'] && $single_list[$val['single_id']] && !$val['floor_layer']) {
                        $val['floor_layer'] = $single_list[$val['single_id']];
                    }
                }
            }

            $selecteds = $_GET['selecteds'];
            $selectedArr = explode(',', $selecteds);

            if (empty($selectedArr)) {
                $selectedArr = array();
            }

            //楼栋
            $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort desc, id asc')->select();
            if($single_list){
                $this->assign('single_list',$single_list);
            }else{
                $this->assign('single_list',[]);
            }
            if($_GET['vacancy_id']){
                $this->assign('vacancy_id',$_GET['vacancy_id']);
            }else{
                $this->assign('vacancy_id','');
            }
            $this->assign('selectedArr', $selectedArr);
            $this->assign('selecteds', $selecteds);
            $this->assign('list',$village_floor_list['list']);
            $this->assign('pagebar',$village_floor_list['pagebar']);
            $this->assign('village_id',$this->village_id);
            $this->display();
        }
    }

    public function choose_floor(){
        //新闻列表-删除 权限
        $selectedArr = $_POST['selecteds'];
        if (empty($selectedArr)) {
            $this->ajaxReturn(array('status'=>0,'data'=>array()));
        }
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['floor_id'] = array('in', $selectedArr);

        $database_house_village_floor = D('House_village_floor');
        $data = $database_house_village_floor->where($where)->field('floor_id,single_id, floor_layer, floor_name')->select();
        // 直接取所有该小区楼栋信息
        $database_house_village_single = D('House_village_single');
        $where_single['status'] = 1;
        $where_single['village_id'] = $this->village_id;
        $single_list = $database_house_village_single->where($where_single)->getField('id, single_name');
        if ($data) {
            $item = [];
            foreach ($data as &$val) {
                $item[] = $val['floor_id'];
                if ($val['single_id'] && $single_list[$val['single_id']]) {
                    $val['floor_layer'] = $single_list[$val['single_id']];
                }
            }
            $selecteds = implode(',', $item);
        }
        if($data){
            $this->ajaxReturn(array('status'=>0,'data'=>$data, 'selecteds' => $selecteds));
        }else{
            $this->ajaxReturn(array('status'=>0,'data'=>array()));
        }
    }



	public function news_del(){
        //新闻列表-删除 权限
        if (!in_array(161, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$news_id = $_GET['news_id'] + 0;
		if(!$news_id){
			$this->error('传递参数有误！~~~');
		}

		$database_house_village_news = D('House_village_news');

		$news_where['news_id'] = $news_id;
		$insert_id = $database_house_village_news->where($news_where)->delete();
		if($insert_id){
		    //todo 同步删除绑定关系
            D('House_village_news_notice')->where(['type'=>1,'news_id'=>$news_id])->delete();
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}
	}

	public function news_edit_do(){
		$news_id = $_POST['news_id'];
		if(IS_POST){

			if(!trim($_POST['title'])){
				$this->error('标题必填！');
			}
			if(empty($_POST['cat_id'])){
				$this->error('分类必选！');
			}
			if(empty($_POST['description'])){
				$this->error('内容必填！');
			}
			$_POST['is_hot'] = intval($_POST['is_hot']);
			$_POST['status'] = intval($_POST['status']);
			$_POST['cat_id'] = intval($_POST['cat_id']);
            $_POST['is_device_show'] = intval($_POST['is_device_show']);
			$_POST['content'] = $_POST['description'];
			$_POST['village_id'] = $this->village_id;
			$floor_txt_arr = $_POST['floor_txt_arr'];
            if($floor_txt_arr) {
            $floor_txt = implode(',', $floor_txt_arr);
                if ($floor_txt) {
                    $_POST['floor_txt'] = $floor_txt;
                }
            }else{
                $_POST['floor_txt'] = '';
            }
			unset($_POST['description']);
			if($news_id){
		        //新闻列表-编辑 权限
		        if (!in_array(160, $this->house_session['menus'])) {
		            $this->error('对不起，您没有权限执行此操作');
		        }
		        if(!empty($_POST['end_time'])){
                    $_POST['end_time']=strtotime($_POST['end_time']);
                }else{
                    $_POST['end_time']=0;
                }

				$condition_village['village_id'] = $this->village_id;
				$condition_village['news_id'] = $news_id;
				//$condition_village['status'] = 1;
				$news_info = D('House_village_news')->where($condition_village)->find();
				if(empty($news_info)){
					$this->error('暂无此新闻');
				}
				$cate = D('House_village_news_category')->where(array('status'=>1,'cat_id'=>$_POST['cat_id'],'village_id'=>$this->village_id))->find();
				if(empty($cate)){
					$this->error('暂无此分类');
				}

                //todo 存在部分转发对象
                $village_news_notice=D('House_village_news_notice')->where(['news_id'=>$news_id])->find();
                if($village_news_notice){
                    $_POST['is_whole']=1;
                }else{
                    $_POST['is_whole']=0;
                }
				$result = D('House_village_news')->where($condition_village)->data($_POST)->save();
				if($result !== false){
				    if($_POST['is_device_show'] == 1){
                        D('House_village_news')->news_edit([
                            'village_id'=>$news_info['village_id'],
                            'news_id'=>array('neq',$news_id)
                        ],0);
                    }
					$this->success('修改成功！',U('News/index'));
				}else{
					$this->error('修改失败！请重试。');
				}
			}else{
		        //新闻列表-添加 权限
		        if (!in_array(159, $this->house_session['menus'])) {
		            $this->error('对不起，您没有权限执行此操作');
		        }
                if(!empty($_POST['end_time'])){
                    $_POST['end_time']=strtotime($_POST['end_time']);
                }else{
                    $_POST['end_time']=0;
                }
				$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
                $_POST['type']=1;
				$result = D('House_village_news')->data($_POST)->add();
				if($result !== false){
				    $village_news_notice=D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>0])->find();
				    if($village_news_notice){
                        D('House_village_news_notice')->where(['uid'=>$this->village_id,'news_id'=>0,'type'=>1])->data(['news_id'=>$result])->save();
                        //todo 存在部分转发对象
                        D('House_village_news')->where(['news_id'=>$result])->data(['is_whole'=>1])->save();
                    }

                    if($_POST['is_device_show'] == 1){
                        D('House_village_news')->news_edit([
                            'village_id'=>$this->village_id,
                            'news_id'=>array('neq',$result)
                        ],0);
                    }
					$this->success('添加成功！',U('News/index'));
				}else{
					$this->error('添加失败！请重试。');
				}
			}
		}
	}
	public function send_over(){
		$this->display();
	}
	// 微信群发
	public function send(){
		$news_id = $_GET['news_id'];
		$send_user_type = $_GET['send_user_type'];
		$send_type = $_GET['send_type'];
        //新闻列表-微信群发 权限
        if (!in_array(162, $this->house_session['menus'])) {
			exit(json_encode(array('code'=>1,'msg'=>'对不起，您没有权限执行此操作')));
        } 
        
		if($news_id){
            $condition_village = [];
			$condition_village['village_id'] = $this->village_id;
			$condition_village['news_id'] = $news_id;
			$condition_village['status'] = 1;
			$news_info = D('House_village_news')->where($condition_village)->find();
			if(empty($news_info)){
				exit(json_encode(array('code'=>1,'msg'=>'暂无此新闻')));
			}
            $villageInfo=M('House_village')->where(array('village_id'=>$this->village_id))->field('*')->find();
			$where_ower = array(
				'b.status'=> 1,
				'b.village_id'=> $this->village_id
			);
            /**  $send_user_type
            1、仅业主
            2、业主及家属
            3、租客
            4、仅工作人员
            5、业主+家属+租客
            6、业主+工作人人员
             ***/
			if ($send_user_type==1) {
				$where_ower['b.type'] = array('in',[0,3]);
			}elseif($send_user_type==2) {
                //todo 过滤访客或出入证人员
                $where_ower['b.type'] = array('in',[0,1,3]);
            } elseif($send_user_type==3) {
				$where_ower['b.type'] = 2;
			}elseif($send_user_type==4) {
                $where_ower['b.type'] = 4;
            } elseif($send_user_type==5) {
                $where_ower['b.type'] = array('in',[0,1,2,3]);
            } elseif($send_user_type==6) {
                $where_ower['b.type'] = array('in',[0,3,4]);
            }
            $floor_arr=[];
            if ($news_info['floor_txt'] && $send_user_type!=3) {
                $floor_arr = explode(',', $news_info['floor_txt']);
                if ($floor_arr && $floor_arr[0]) {
                    //老版查询条件
//                    $where_ower['b.floor_id'] = ['in', $floor_arr];
                }
            }

            // 小区下所有的业主
            $where_ower['_string'] = "u.openid !=''" ;


//            $where_ower['n.news_id'] =$news_info['news_id'];
            /**
             * todo 逻辑改版
             */
            $news_notice=D('House_village_news_notice')->where(['news_id'=>$news_info['news_id']])->field('room_id')->select();
            if($news_notice && $send_user_type!=3){

                $room_id=array_values(array_unique(array_column($news_notice,'room_id')));
                //todo 查询该房间里用户
                if($send_user_type==2){
                    $room_id[]=0;
                }
                $where_ower['b.vacancy_id'] = ['in', $room_id];
            }
            $users = D('House_village_user_bind')
                ->join("as b LEFT JOIN  ".C('DB_PREFIX')."user as u ON u.uid=b.uid")
				->where($where_ower)
                ->field('b.pigcms_id,b.uid,b.type,b.village_id,u.openid,u.nickname,b.phone,b.vacancy_id')
                ->group('u.openid,b.type')
                ->select();


//			$users = D('House_village')
//				->join("as h LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as b ON h.village_id=b.village_id LEFT JOIN  ".C('DB_PREFIX')."user as u ON u.uid=b.uid")
//				->where($where_ower)->field('b.pigcms_id,b.uid,u.openid,u.nickname,b.type,b.village_id,h.property_id')->group('u.openid,b.type')->select();
            $property_id=0;
            if($villageInfo){
                $property_id=$villageInfo['property_id'];
            }
			if(!$users){
				exit(json_encode(array('code'=>1,'msg'=>'暂无发送对象')));
			}
            $condition_village = [];
            $condition_village['news_id'] = $news_id;
            D('House_village_news')->where($condition_village)->save(array('is_notice'=>1));

            $param = [
                'plan_send' =>1
            ];
            $site_url = $this->config['site_url'];
            $base_url = D('House_village')->base_url;
			if($send_type == 1){
                $external_user_id_txt = '';
			    foreach ($users as $k=>$v){
			        $ExternalUserID = M('House_contact_way_user')->where(['status'=>1,'bind_id'=>$v['pigcms_id']])->getField('ExternalUserID');
			        if($ExternalUserID){
                        $external_user_id_txt .= $ExternalUserID.',';
                        $userInfo[] = $v;
                        unset($users[$k]);
                    }
                }
			    if(!empty($external_user_id_txt)){
                    $external_user_id_txt = rtrim($external_user_id_txt,',');
                    $where['ExternalUserID'] = array('in',$external_user_id_txt);
                    $sender_list = M('House_contact_way_user')->field('UserID')->where($where)->select();
                    $sender_txt = '';
                    foreach ($sender_list as $k=>$sender_info){
                        $sender_txt .= $sender_info['UserID'].',';
                    }
                    $sender_txt = rtrim($sender_txt,',');
                    $message_id = M('Village_qywx_message')->data([
                        'village_id'=>$this->village_id,
                        'property_id'=>$property_id,
                        'message_name'=>$news_info['title'],
                        'send_type'=>1,
                        'sender'=>$sender_txt,
                        'external_userid'=>$external_user_id_txt,
                        'send_status'=>3,
                        'send_time'=>time(),
                        'create_time'=>time()
                    ])->add();
                    if($message_id){
                        M('Village_qywx_message_detail')->data([
                            'message_id'=>$message_id,
                            'type'=>3,
                            'content'=>$news_info['title'],
                            'share_url'=>$site_url . $base_url . 'pages/village/index/villagecontent?village_id=' . $userInfo[$k]['village_id'] . ($userInfo[$k]['pigcms_id'] ? '&pigcms_id=' . $userInfo[$k]['pigcms_id'] : '') . '&news_id=' . $news_id,
                            'add_time'=>time()
                        ])->add();

                        import('@.ORG.plan');
                        $plan_class = new plan();
                        $param = array(
                            'plan_time'=>time(),
                            'space_time'=>0,
                            'add_time'=>time(),
                            'file'=>'sub_village_qywx_send_message',
                            'time_type'=>1,
                            'unique_id'=>'qywx_message'
                        );
                        $plan_class->addTask($param);
                        
                    }
                }
			    if(!empty($users)){
                    $users = array_values($users);
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    foreach ($users as $userInfo){
                        $href = $site_url . $base_url . 'pages/village/index/villagecontent?village_id=' . $userInfo['village_id'] . ($userInfo['pigcms_id'] ? '&pigcms_id=' . $userInfo['pigcms_id'] : '') . '&news_id=' . $news_id;
                        switch ($send_user_type) {
                            case '1': // 仅业主
                                if ($userInfo['type'] != 4) {
                                    // 新增模板消息（消息发送状态提醒）
                                    //todo app 极光推送
                                    D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);

                                    $model->sendTempMsg('OPENTM405462911', array(
                                            'href' => $href, 
                                            'wecha_id' => $userInfo['openid'], 
                                            'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                            'keyword1' => ''.C('config.house_name').'新闻', 
                                            'keyword2' =>'已发送',
                                            'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                            'keyword4' => $userInfo['nickname'], 
                                            'remark' => '\n请点击查看详细信息！',
                                            'new_info' => [//新版本发送需要的信息
                                                'tempKey'=>'44716',//新模板号
                                                'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                                'phrase2'=>'新闻',//订单类型
                                                'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                                'thing11'=>'无',//服务地址
                                            ],
                                        ),0,$property_id,2,$param);
                                }
                                break;
                            case '2': // 业主和工作人员
                                // 新增模板消息（消息发送状态提醒）
                                //todo app 极光推送
                                D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);
                                $model->sendTempMsg('OPENTM405462911', array(
                                    'href' => $href, 
                                    'wecha_id' => $userInfo['openid'], 
                                    'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                    'keyword1' => ''.C('config.house_name').'新闻', 
                                    'keyword2' =>'已发送',
                                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                    'keyword4' => $userInfo['nickname'], 
                                    'remark' => '\n请点击查看详细信息！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey'=>'44716',//新模板号
                                        'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                        'phrase2'=>'新闻',//订单类型
                                        'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                        'thing11'=>'无',//服务地址
                                    ],
                                ),0,$property_id,2,$param);
                                break;
                            case '3': // 仅工作人员
                                if ($userInfo['type'] == 4) {
                                    // 新增模板消息（消息发送状态提醒）

                                    //todo app 极光推送
                                    D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);

                                    $model->sendTempMsg('OPENTM405462911', array(
                                        'href' => $href, 
                                        'wecha_id' => $userInfo['openid'], 
                                        'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                        'keyword1' => ''.C('config.house_name').'新闻', 
                                        'keyword2' =>'已发送',
                                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                        'keyword4' => $userInfo['nickname'], 
                                        'remark' => '\n请点击查看详细信息！',
                                        'new_info' => [//新版本发送需要的信息
                                            'tempKey'=>'44716',//新模板号
                                            'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                            'phrase2'=>'新闻',//订单类型
                                            'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                            'thing11'=>'无',//服务地址
                                        ],
                                    ),0,$property_id,2,$param);
                                }
                                break;
                        }
                    }
                }
                exit(json_encode(array('code'=>0,'msg'=>'发送完毕')));
            }
			elseif ($send_type == 2){
                $external_user_id_txt = '';
                foreach ($users as $k=>$v){
                    $ExternalUserID = M('House_contact_way_user')->where(['status'=>1,'phone'=>$v['phone'],'room_id'=>$v['vacancy_id']])->getField('ExternalUserID');
                    if($ExternalUserID){
                        $external_user_id_txt .= $ExternalUserID.',';
                        $userInfo[] = $v;
                    }
                }
                if(!empty($external_user_id_txt)){
                    $external_user_id_txt = rtrim($external_user_id_txt,',');
                    $where['ExternalUserID'] = array('in',$external_user_id_txt);
                    $sender_list = M('House_contact_way_user')->field('UserID')->where($where)->select();
                    $sender_txt = '';
                    foreach ($sender_list as $k=>$sender_info){
                        $sender_txt .= $sender_info['UserID'].',';
                    }
                    $sender_txt = rtrim($sender_txt,',');
                    $message_id = M('Village_qywx_message')->data([
                        'village_id'=>$this->village_id,
                        'property_id'=>$property_id,
                        'message_name'=>$news_info['title'],
                        'send_type'=>1,
                        'sender'=>$sender_txt,
                        'external_userid'=>$external_user_id_txt,
                        'send_status'=>3,
                        'send_time'=>time(),
                        'create_time'=>time()
                    ])->add();
                    if($message_id){
                        M('Village_qywx_message_detail')->data([
                            'message_id'=>$message_id,
                            'type'=>3,
                            'content'=>$news_info['title'],
                            'share_url'=>$site_url . $base_url . 'pages/village/index/villagecontent?village_id=' . $userInfo[$k]['village_id'] . ($userInfo[$k]['pigcms_id'] ? '&pigcms_id=' . $userInfo[$k]['pigcms_id'] : '') . '&news_id=' . $news_id,
                            'add_time'=>time()
                        ])->add();

                        import('@.ORG.plan');
                        $plan_class = new plan();
                        $param = array(
                            'plan_time'=>time(),
                            'space_time'=>0,
                            'add_time'=>time(),
                            'file'=>'sub_village_qywx_send_message',
                            'time_type'=>1,
                            'unique_id'=>'qywx_message'
                        );
                        $plan_class->addTask($param);
                    }
                }
                exit(json_encode(array('code'=>0,'msg'=>'发送完毕')));
            }
			else{
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                foreach ($users as $userInfo){
                    $href = $site_url . $base_url . 'pages/village/index/villagecontent?village_id=' . $userInfo['village_id'] . ($userInfo['pigcms_id'] ? '&pigcms_id=' . $userInfo['pigcms_id'] : '') . '&news_id=' . $news_id;
                    switch ($send_user_type) {
                        case '1': // 仅业主
                            if ($userInfo['type'] != 4) {
                                // 新增模板消息（消息发送状态提醒）

                                //todo app 极光推送
                                D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);

                                $model->sendTempMsg('OPENTM405462911', array(
                                    'href' => $href, 
                                    'wecha_id' => $userInfo['openid'], 
                                    'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                    'keyword1' => ''.C('config.house_name').'新闻', 
                                    'keyword2' =>'已发送',
                                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                    'keyword4' => $userInfo['nickname'], 
                                    'remark' => '\n请点击查看详细信息！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey'=>'44716',//新模板号
                                        'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                        'phrase2'=>'新闻',//订单类型
                                        'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                        'thing11'=>'无',//服务地址
                                    ],
                                ),0,$property_id,2,$param);
                            }
                            break;
                        case '2': // 业主和工作人员
                            // 新增模板消息（消息发送状态提醒）

                            //todo app 极光推送
                            D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);

                            $model->sendTempMsg('OPENTM405462911', array(
                                'href' => $href, 
                                'wecha_id' => $userInfo['openid'], 
                                'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                'keyword1' => ''.C('config.house_name').'新闻', 
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                'keyword4' => $userInfo['nickname'], 
                                'remark' => '\n请点击查看详细信息！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey'=>'44716',//新模板号
                                    'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                    'phrase2'=>'新闻',//订单类型
                                    'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                    'thing11'=>'无',//服务地址
                                ],
                            ),0,$property_id,2,$param);
                            break;
                        case '3': // 仅工作人员
                            if ($userInfo['type'] == 4) {
                                // 新增模板消息（消息发送状态提醒）
                                //todo app 极光推送
                                D('House_village_repair_log')->send_app(['uid'=>$userInfo['uid']],C('config.house_name').'新闻','您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】',$href);

                                $model->sendTempMsg('OPENTM405462911', array(
                                    'href' => $href, 
                                    'wecha_id' => $userInfo['openid'], 
                                    'first' => '您好，'.$this->house_session['village_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 
                                    'keyword1' => ''.C('config.house_name').'新闻', 
                                    'keyword2' =>'已发送',
                                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                                    'keyword4' => $userInfo['nickname'], 
                                    'remark' => '\n请点击查看详细信息！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey'=>'44716',//新模板号
                                        'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                                        'phrase2'=>'新闻',//订单类型
                                        'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                                        'thing11'=>'无',//服务地址
                                    ],
                                ),0,$property_id,2,$param);
                            }
                            break;
                    }
                    if(in_array($send_user_type,[2,3])){
                        $param=[
                            'site_url'=>$site_url,
                            'base_url'=>$base_url,
                            'village_id'=>$this->village_id,
                            'village_name'=>$this->house_session['village_name'],
                            'news_id'=>$news_id,
                            'news_title'=>$news_info['title'],
                            'property_id'=>$this->house_session['property_id']
                        ];
                        D('House_worker')->send_work($param);
                    }
                }
                exit(json_encode(array('code'=>0,'msg'=>'发送完毕')));
            }
		}
	}

	// 新闻分类
	public function cate(){
        //新闻分类-查看 权限
        if (!in_array(155, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$condition['village_id'] = $this->village_id;
		$news_category = D('House_village_news_category')->where($condition)->order('cat_sort DESC,cat_status DESC')->select();

		$this->assign('news_category',$news_category);
		$this->display();
	}

	// 新闻分类
	public function cate_edit(){
		$cate_id = intval($_GET['cat_id']);
		if($cate_id){

			$condition['village_id'] = $this->village_id;
			$condition['cat_id'] = $_GET['cat_id'];
			$news_category = D('House_village_news_category')->where($condition)->find();
			if(!$news_category){
				$this->error('此分类不存在');
			}

			$this->assign('cate_id',$cate_id);
			$this->assign('cate_info',$news_category);

		}
		$this->display();
	}

	// 新闻分类
	public function cate_edit_do(){
		$cate_id = intval($_POST['c_id']);
		if($cate_id){
	        //新闻分类-编辑 权限
	        if (!in_array(157, $this->house_session['menus'])) {
	            $this->error('对不起，您没有权限执行此操作');
	        }

			$condition['village_id'] = $this->village_id;
			$condition['cat_id'] = $cate_id;
			$news_category = D('House_village_news_category')->where($condition)->find();
			if(!$news_category){
				$this->error('此分类不存在');
			}			

			if(!trim($_POST['cat_name'])){
				$this->error('分类名称必填！');
			}

			$data['cat_name'] = $_POST['cat_name'];
			$data['cat_sort'] = $_POST['cat_sort'];
			$data['cat_status'] = intval($_POST['cat_status']);
			$result = D('House_village_news_category')->where($condition)->save($data);

			if($result !== false){
				$this->success('修改成功！',U('News/cate'));
			}else{
				$this->error('修改失败！请重试。');
			}
		}else{
			// 添加
	        //新闻分类-添加 权限
	        if (!in_array(156, $this->house_session['menus'])) {
	            $this->error('对不起，您没有权限执行此操作');
	        }

			if(!trim($_POST['cat_name'])){
				$this->error('分类名称必填！');
			}

			$data['cat_name'] = $_POST['cat_name'];
			$data['cat_sort'] = intval($_POST['cat_sort']);
			$data['cat_status'] = intval($_POST['cat_status']);
			$data['village_id'] = $this->village_id;

			$result = D('House_village_news_category')->data($data)->add();

			if($result !== false){
				$this->success('添加成功！',U('News/cate'));
			}else{
				$this->error('添加失败！请重试。');
			}
		}

		$this->assign('news_category',$news_category);
	}

	//新闻评论列表
	public function reply(){
        //新闻评论列表-查看 权限
        if (!in_array(122, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$condition['village_id'] = $this->village_id;
		$news = D('House_village_news_reply')->getlist($condition);
        $is_check =  D('House_village_config')->where($condition)->getField('village_news_is_need_check');

        $this->assign('is_check',$is_check);
		$this->assign('news',$news);
		$this->display();
	}

	public function read(){
        //已读 权限
        if (!in_array(124, $this->house_session['menus'])) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作'));
        }

		if(IS_AJAX && $_POST['cmsid']){
			$condition['village_id'] = $this->village_id;
			$condition['is_read'] = 0;
			$condition['pigcms_id'] = $_POST['cmsid'];

			$data['is_read'] = 1;
			$result = D('House_village_news_reply')->where($condition)->data($data)->save();

			if($result){
				$this->ajaxReturn(array('status'=>1,'msg'=>'已读'));
			}

			$this->ajaxReturn(array('status'=>0,'msg'=>'失败'));
		}
	}
    //删除评论
    public function reply_del(){
        //删除 权限
        if (!in_array(125, $this->house_session['menus'])) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作'));
        }

        if(IS_AJAX && $_POST['cmsid']){
            $condition['village_id'] = $this->village_id;

            $condition['pigcms_id'] = $_POST['cmsid'];

            $result = D('House_village_news_reply')->where($condition)->delete();

            if($result){
                $this->ajaxReturn(array('status'=>1,'msg'=>'已删除'));
            }

            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
        }
    }
    //评论显示隐藏
    public function change_status()
    {
        //前台是否显示 权限
        if (!in_array(126, $this->house_session['menus'])) {
            exit('对不起，您没有权限执行此操作');
        }

        $status = $_POST['type'] == 'open' ? '1' : '2';
        if (D('House_village_news_reply')->where(array('pigcms_id' => $_POST['id']))->save(array('status' => $status))) {
            exit('1');
        } else {
            exit;
        }
    }
    //是否需要审核
    public function is_check()
    {
        //开启关闭评论审核 权限
        if (!in_array(123, $this->house_session['menus'])) {
            exit('对不起，您没有权限执行此操作');
        }

        $status = $_POST['type'] == 'open' ? '1' : '2';
        $now_config = D('House_village_config')->where(['village_id' => $this->village_id])->find();
		if($now_config){
			if (D('House_village_config')->where(['village_id' => $this->village_id])->save(array('village_news_is_need_check' => $status))) {
				exit('1');
			} else {
				exit('操作失败');
			}
		}else{
			if (D('House_village_config')->data(['village_id' => $this->village_id, 'village_news_is_need_check' => $status])->add()) {
				exit('1');
			} else {
				exit('操作失败');
			}
		}
    }
}