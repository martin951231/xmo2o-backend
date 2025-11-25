<?php
/*
  * 客户定制-商家积分 
  * @date 2021/09/10
*/
class Merchant_scoreAction extends BaseAction{
	/*
	  *商家积分获得释放记录
	  * @date 2021/09/10
	*/
	public function index(){	
		
			$where['mer_id'] = $this->merchant_session['mer_id'];

			// 统计锁定积分
			$lock_score = D('Merchant_get_score')->field('sum(score_count) as score_count,sum(score_get) as score_get')->where($where)->find();
      $this->assign('lock_score', $lock_score['score_count']-$lock_score['score_get']);

			if($_GET['start_time'] && $_GET['end_time'] ){
				$start_time = date('Ymd', strtotime($_GET['start_time']));
				$end_time = date('Ymd', strtotime($_GET['end_time']));
				$where['get_date'] = ['between', [$start_time, $end_time]];
			}

			$count = D('Merchant_get_score')->where($where)->count();

      import('@.ORG.merchant_page');
      $page = new Page($count, 20);

			$list = D('Merchant_get_score')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order('get_date DESC')->select();


			foreach ($list as $k => &$v) {
				$v['get_date'] = date('Y-m-d', strtotime($v['get_date']));
			}

      $pagebar = $page->show();
      $this->assign('pagebar', $pagebar);
      $this->assign('list', $list);
			$this->display();
	}

	/*
	  * 商家积分获得记录
	  * @date 2021/09/10
	*/
	public function log_list(){	
		
			$where['mer_id'] = $this->merchant_session['mer_id'];
			
			// 统计锁定积分
			$lock_score = D('Merchant_get_score')->field('sum(score_count) as score_count,sum(score_get) as score_get')->where($where)->find();
      $this->assign('lock_score', $lock_score['score_count']-$lock_score['score_get']);

			if($_GET['start_time'] && $_GET['end_time'] ){
					$start_time = strtotime($_GET['start_time']);
					$end_time = strtotime($_GET['end_time']) + 86400;
					$where['add_time'] = ['between', [$start_time, $end_time]];
			}


			$get_alias_c_name = D("Merchant_money_list")->get_alias_c_name();
			$get_alias_c_name['system_operate'] = '管理员操作';
	    $this->assign("get_alias_c_name", $get_alias_c_name);


			$count = D('Merchant_get_score_order_log')->where($where)->count();

	    import('@.ORG.merchant_page');
	    $page = new Page($count, 20);

			$list = D('Merchant_get_score_order_log')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order('add_time DESC')->select();


			foreach ($list as $k => &$v) {
	        $v['order_type_txt'] = $get_alias_c_name[$v['order_type']] ?? '无';
	        if($v['order_type'] == 'system_operate' && $v['remark']){
	        		$v['order_type_txt'] .= '：'.$v['remark'];
	        }
			}

      $pagebar = $page->show();
      $this->assign('pagebar', $pagebar);
      $this->assign('list', $list);
			$this->display();
	}

}
?>