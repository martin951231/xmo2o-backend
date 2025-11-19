<?php
class Admin_logAction extends BaseAction{
	public function index(){
		$database_admin_log = M('Admin_log');
		
		$where = array();
		
		if($_GET['do']){
			$do = explode('/',$_GET['do']);
			$_GET['module'] = $do[0];
			$_GET['action'] = $do[1];
		}
		
		if($_GET['module']){
			$where['module'] = safeValue($_GET['module']);
		}
		if($_GET['action']){
			$where['action'] = safeValue($_GET['action']);
		}
		
		if($_GET['content']){
			$content_arr = explode('&&',$_GET['content']);
			foreach($content_arr as $key=>$value){
				$content_arr[$key] = '%'.$value.'%';
			}
			$where['content'] = array('like',$content_arr,'OR');
		}
		
		$page_tip_arr = array(
			'Deliver/changeModel' => array(
				'name' => '调度模式变更',
				'info' => '[model] => 1  变更为手动调度，[model] => 0 为 变更为配送员抢单',
			),
		);
		
		$count_log = $database_admin_log->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_log,30);
		$log_list = $database_admin_log->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('log_list',$log_list);
		
		if($_GET['module'] && $_GET['action']){
			$pageStr = $_GET['module'].'/'.$_GET['action'];
			$this->assign('pageStr',$pageStr);
			$this->assign('page_tip',$page_tip_arr[$pageStr]);
		}
		$this->assign('page_tip_arr',$page_tip_arr);
		
		
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		
		$this->display();
	}
	public function merchant(){
		$database_admin_log = M('Merchant_log');
		
		$where = array();
		
		if($_GET['do']){
			$do = explode('/',$_GET['do']);
			$_GET['module'] = $do[0];
			$_GET['action'] = $do[1];
		}
		
		if($_GET['module']){
			$where['module'] = safeValue($_GET['module']);
		}
		if($_GET['action']){
			$where['action'] = safeValue($_GET['action']);
		}
		
		if($_GET['content']){
			$content_arr = explode('&&',$_GET['content']);
			foreach($content_arr as $key=>$value){
				$content_arr[$key] = '%'.$value.'%';
			}
			$where['content'] = array('like',$content_arr,'OR');
		}
		
		$page_tip_arr = array(
			// 'Deliver/changeModel' => array(
				// 'name' => '调度模式变更',
				// 'info' => '[model] => 1  调整为手动调度，[model] => 0 为 调整为配送员抢单',
			// ),
		);
		
		$count_log = $database_admin_log->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_log,30);
		$log_list = $database_admin_log->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('log_list',$log_list);
		
		if($_GET['module'] && $_GET['action']){
			$pageStr = $_GET['module'].'/'.$_GET['action'];
			$this->assign('pageStr',$pageStr);
			$this->assign('page_tip',$page_tip_arr[$pageStr]);
		}
		$this->assign('page_tip_arr',$page_tip_arr);
		
		
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		
		$this->display();
	}
	public function house(){
		$database_admin_log = M('House_log');
		
		$where = array();
		
		if($_GET['do']){
			$do = explode('/',$_GET['do']);
			$_GET['module'] = $do[0];
			$_GET['action'] = $do[1];
		}
		
		if($_GET['module']){
			$where['module'] = safeValue($_GET['module']);
		}
		if($_GET['action']){
			$where['action'] = safeValue($_GET['action']);
		}
		
		if($_GET['content']){
			$content_arr = explode('&&',$_GET['content']);
			foreach($content_arr as $key=>$value){
				$content_arr[$key] = '%'.$value.'%';
			}
			$where['content'] = array('like',$content_arr,'OR');
		}
		
		$page_tip_arr = array(
			// 'Deliver/changeModel' => array(
				// 'name' => '调度模式变更',
				// 'info' => '[model] => 1  调整为手动调度，[model] => 0 为 调整为配送员抢单',
			// ),
		);
		
		$count_log = $database_admin_log->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_log,30);
		$log_list = $database_admin_log->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('log_list',$log_list);
		
		if($_GET['module'] && $_GET['action']){
			$pageStr = $_GET['module'].'/'.$_GET['action'];
			$this->assign('pageStr',$pageStr);
			$this->assign('page_tip',$page_tip_arr[$pageStr]);
		}
		$this->assign('page_tip_arr',$page_tip_arr);
		
		
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		
		$this->display();
	}
}