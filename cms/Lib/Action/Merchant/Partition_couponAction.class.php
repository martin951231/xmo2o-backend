<?php 
/*
 *2019年6月27日11:13:46
 *平台瓜分券功能
 */
class Partition_couponAction extends BaseAction {
	public function index(){
		if (!empty($_GET['keyword'])) {
			if ($_GET['searchtype'] == 'pigcms_id') {
				$condition_coupon['pigcms_id'] = $_GET['keyword'];
			} else if ($_GET['searchtype'] == 'name') {
				$condition_coupon['name'] = array('like', '%' . $_GET['keyword'] . '%');
			}
		}
		$condition_coupon['delete'] = 0;
		$condition_coupon['mer_id'] = $this->merchant_session['mer_id'];
		isset($_GET['status']) && $_GET['status']>=0 && $condition_coupon['status'] = $_GET['status'];
		//排序 /*/
		$order_string = '`pigcms_id` DESC';
		
		$database_partition_coupon = D('Merchant_partition_coupon');
		$count_count = $database_partition_coupon->where($condition_coupon)->count();
		import('@.ORG.system_page');
		$p = new Page($count_count, 15);
		$coupon_list = $database_partition_coupon->where($condition_coupon)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$city_id_arr = array();
		foreach($coupon_list as $key=>&$v){

			if($v['end_time']<$_SERVER['REQUEST_TIME']){
				$database_partition_coupon->where(array('pigcms_id'=>$v['pigcms_id']))->setField('status',2);
				$v['status']=2;
			}
			// 已瓜分
			$v['has_partition'] = M('Merchant_partition_coupon_hadpull')->where(array('coupon_id'=>$v['pigcms_id'],'status'=>1,'is_leader'=>1))->count();

			// 进行中
			$v['going_num'] = M('Merchant_partition_coupon_hadpull')->where(array('coupon_id'=>$v['pigcms_id'],'status'=>0,'is_leader'=>1))->count();
			$v['sy_num'] = $v['num'] - $v['had_pull'];
			
		}
		
		$this->assign('coupon_list',$coupon_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function add(){
		if(IS_POST){
			$data = $_POST;
			$data['start_time']=strtotime($data['start_time']);
			$data['end_time']=strtotime($data['end_time']);
			if($data['end_time']<=$data['start_time']){
				$this->error('活动起始时间设置有误');
			}

			$data['effective_time']= $data['day']*86400+$data['hour']*3600+$data['minute']*60;
			if (!$data['effective_time']) {
				$this->error('瓜分券有效期必填');
			}

			if ($data['time_type']==0) {
				if (!$data['use_start_time']||!$data['use_end_time']) {
					$this->error('请选择可用券时间');
				}
				$data['use_start_time']=strtotime($data['use_start_time']);
				$data['use_end_time']=strtotime($data['use_end_time']);
				if($data['use_end_time']<=$data['use_start_time']){
					$this->error('可用券时间设置有误');
				}
				$data['use_time'] = 0;
			}else{
				if (!$data['use_time']) {
					$this->error('请填写可用券时间');
				}
				$data['use_start_time'] = 0;
				$data['use_end_time'] = 0;
			}	

			if ($data['use_condition']==1) {
				if (!$data['order_money']) {
					$this->error('请输入正确金额');
				}
			}else{
				$data['order_money'] = 0;
			}

			if ($data['partition_times_type']==1) {
				if (!intval($data['partition_times'])) {
					$this->error('请输入每人限瓜分次数');
				}
			}else{
				$data['partition_times'] = 0;
			}

			$data['store_id'] = implode(',',$data['store_id']);
			$data['mer_id'] = $this->merchant_session['mer_id'];
			$data['add_time']=$data['last_time']=time();
			if($id = D('Merchant_partition_coupon')->add($data)){
				$this->success('添加瓜分券成功！');
			}else{
				$this->error('添加失败！');
			}
		}else {

            $store_list = D('Merchant_store')->get_store_list_by_merId($this->merchant_session['mer_id']);
            $this->assign('store_list',$store_list);
			$this->display();
		}
	}

	public function edit(){
		if(IS_POST){
			$coupon = D('Merchant_partition_coupon')->where(array('pigcms_id'=>$_POST['pigcms_id']))->find();
			$data['end_time']=strtotime($_POST['end_time']);
			if($data['end_time']<$coupon['end_time']){
				$this->error('活动结束时间不能小于当前结束时间');
			}elseif ($data['end_time']>$coupon['end_time']&&$data['end_time']>time()) {
				$data['status'] = 1;
			}
			if(D('Merchant_partition_coupon')->where(array('pigcms_id'=>$_POST['pigcms_id']))->save($data) !== false){
				$this->success('保存成功！');
			}else{
				$this->error('保存失败！');
			}
		}else {
			$coupon = D('Merchant_partition_coupon')->where(array('pigcms_id'=>$_GET['pigcms_id']))->find();
			$this->assign("coupon",$coupon);

            $store_list = D('Merchant_store')->field('name')->where(array('store_id'=>array('in',$coupon['store_id'])))->select();
			$this->assign("store_list",$store_list);
			$this->display();
		}
	}

	public  function  had_pull(){
		$order_string = 'h.receive_time DESC ,h.id DESC';
		$where['h.uid']=array('neq','');
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'name') {
				$where['c.name'] =  array('like', "%".$_GET['keyword']."%");
			} elseif ($_GET['searchtype'] == 'nickname') {
				$where['u.nickname'] =array('like', "%".$_GET['keyword']."%");
			}
		}
		$coupon = M('System_coupon_hadpull');
		$count_count = $coupon->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id = c.coupon_id')->join(C('DB_PREFIX').'user u ON h.uid = u.uid')->field('h.id,c.name,u.nickname,h.num,h.receive_time,h.is_use,h.phone')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_count, 15);
		$coupon_list = $coupon->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id = c.coupon_id')->join(C('DB_PREFIX').'user u ON h.uid = u.uid')->field('h.id,c.name,u.nickname,h.num,h.receive_time,h.is_use,h.phone')->where($where)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		$this->assign('coupon_list',$coupon_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);

		$this->display();
	}

	public  function delete_coupon(){
		$pigcms_id = $_GET['pigcms_id'];
		$coupon = M('Merchant_partition_coupon')->where(array('pigcms_id'=>$pigcms_id))->find();
		M('Merchant_partition_coupon')->where(array('pigcms_id'=>$pigcms_id))->setField('delete',1);
		$this->success('删除成功');
	}

	public  function stop_coupon(){
		$pigcms_id = $_GET['pigcms_id'];
		$coupon = M('Merchant_partition_coupon')->where(array('pigcms_id'=>$pigcms_id))->find();
		M('Merchant_partition_coupon')->where(array('pigcms_id'=>$pigcms_id))->setField('status',0);
		$this->success('操作成功');
	}

	public function url_qrcode()
	{
		$pigcms_id = intval($_GET['pigcms_id']);
		$qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=Partition_coupon&a=partition_coupon_detail&type=merchang&id=' . $pigcms_id;
		import('@.ORG.phpqrcode');
		$size = $_GET['size'] ? $_GET['size']: 10;
		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);
	}
}
