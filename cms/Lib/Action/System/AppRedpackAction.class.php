<?php

class AppRedpackAction extends BaseAction
{
	public function index()
	{
		import('@.ORG.system_page');

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$condition_user['_string'] =" (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		$count = M('Redpack_cycle')->where($condition_user)->count();

		$p = new Page($count,20);
		$redpack_list = M('Redpack_cycle')->where($condition_user)->order('id DESC')->limit($p->firstRow,$p->listRows)->select();
		$this->assign('redpack_list',$redpack_list);
		$database_user = D('User');
		$user_balance	=	array(
				'count'	=>	$database_user->sum('now_money'),
				'open'	=>	$database_user->where(array('status'=>1))->sum('now_money'),
				'close'	=>	$database_user->where(array('status'=>0))->sum('now_money'),
				'score'	=>	$database_user->where(array('status'=>1))->sum('score_count'),
				'redpack_money'	=>	M('User_redpack_history')->sum('money'),
		);

		$this->assign('user_balance', $user_balance);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function setting()
	{
		$this->display();
	}

	public function hadpull_list()
	{
		import('@.ORG.system_page');

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$condition_user['_string'] =" (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}
		$condition_user['fid']  =$_GET['id'];
		$count = M('User_redpack_history')->where($condition_user)->count();

		$p = new Page($count,20);
		$redpack_list = M('User_redpack_history')->where($condition_user)->field('h.*,u.nickname')->join('as h LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where(array('fid'=>$_GET['id']))->order('id DESC')->limit($p->firstRow,$p->listRows)->select();
		$this->assign('redpack_list',$redpack_list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	//小程序列表
	public function app_list()
    { 
        $database_app_other = D('App_other');
        $app_list = $database_app_other->field(true)->select();       
        $this->assign('app_list', $app_list);
        $this->display();
    }

	public function app_edit(){
        $database_app_other = D('App_other');
		$id = intval($_GET['id']);
		if ($id) {
			$condition['pigcms_id'] = $id;
			$detail = $database_app_other->field(true)->where($condition)->find();
			$this->assign('detail',$detail);
			if(empty($detail)){
				$this->frame_error_tips('该APP不存在！');
			}
		}
		$this->display();
	}

	public function app_amend(){
        $database_app_other = D('App_other');
		$pigcms_id = intval($_POST['id']);

		$data = array();
		if(!$_POST['name']){
			$this->frame_submit_tips(0,'APP名称不能为空');
		}

		if(!$_POST['url_scheme']){
			$this->frame_submit_tips(0,'url scheme不能为空');
		}
		$data['name'] = $_POST['name'];
		$data['url_scheme'] = $_POST['url_scheme'];
		if ($pigcms_id) {
			$condition['pigcms_id'] = $pigcms_id;
			if($database_app_other->where($condition)->data($data)->save() !== false){
            	S('app_other_list',null);
				$this->frame_submit_tips(1,'编辑成功！');
			}else{
				$this->frame_submit_tips(0,'编辑失败！请重试~');
			}
		}else{
			$data['time'] = $_SERVER['REQUEST_TIME'];
			// 只能添加10个
			$count = $database_app_other->count();
			if ($count >= 10) {
				$this->frame_submit_tips(0,'最多只能添加10个！');
			}
			if($database_app_other->data($data)->add()){
            	S('app_other_list',null);
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}
		
	}

	public function app_del(){
        $database_app_other = D('App_other');
		$condition['pigcms_id'] = intval($_POST['id']);
		if($database_app_other->where($condition)->delete()){
            S('app_other_list',null);
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}
}
