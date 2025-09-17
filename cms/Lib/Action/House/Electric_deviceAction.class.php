<?php
/*
 * 电瓶车管理
 *
 */
class Electric_deviceAction extends BaseAction {
	public function index(){
		$where = [];
		$where['village_id'] = $this->house_session['village_id'];
		$where['status'] = ['neq', 4];
		
		$device_list = M('House_electric_device')->where($where)->order('`id` DESC')->select();
		$this->assign('device_list', $device_list);
		
		//今日开门数量
		$condition_count_today['village_id'] = $this->house_session['village_id'];
        $condition_count_today['add_time'] = array('egt',strtotime(date('Y-m-d 00:00:00')));
        $today_count = M('In_park_electriccar')->where($condition_count_today)->count();
        $this->assign('today_count',$today_count);
		
		
		//昨日开门数据
        $condition_count_yesterday['village_id'] = $this->house_session['village_id'];
        $condition_count_yesterday['add_time'] = array('between',strtotime(date("Y-m-d 00:00:00",strtotime("-1 day"))).','.strtotime(date('Y-m-d 00:00:00')));
        $yesterday_count = M('In_park_electriccar')->where($condition_count_yesterday)->count();
        $this->assign('yesterday_count',$yesterday_count);
		
		
		
		$this->display();
	}
	
	public function add(){
		if($_POST){
			$data = array();
            $data['village_id']	 = $this->house_session['village_id'];
            $data['device_id']	 = trim($_POST['device_id']);
            $data['device_name'] = trim($_POST['device_name']);
            $data['status'] = '1';
            $data['add_time'] = time();

            $whereDevice = [
                'device_id' => $data['device_id'],
                'status' => 1,
            ];
            $repeatId = D('House_electric_device')->where($whereDevice)->find();
            if ($repeatId) {
                $this->error('当前设备id已经存在！');exit;
            }
            $data['remark'] = htmlspecialchars(trim($_POST['remark']));
			
			$device_id = D('House_electric_device')->data($data)->add();
            if($device_id){
                $this->success('新增设备成功！', U('index'));
            }else{
                $this->error('新增设备失败！');exit;
            }
		}else{
			$this->display();
		}
	}
	
	public function edit(){
		if($_POST){
			$where = [];
            $where['id']	 = trim($_POST['id']);
            $where['village_id'] = $this->house_session['village_id'];
			
			$data = array();
            $data['device_id']	 = trim($_POST['device_id']);
            $data['device_name'] = trim($_POST['device_name']);

            $whereDevice = [
                'id' => ['neq',$data['id']],
                'device_id' => $data['device_id'],
                'status' => 1,
            ];
            $repeatId = D('House_electric_device')->where($whereDevice)->find();
            if ($repeatId) {
                $this->error('当前设备id已经存在,请更改设备id，或者先联系对应管理员删除对应设备！');exit;
            }
            $data['remark'] = htmlspecialchars(trim($_POST['remark']));
			
			$device_id = M('House_electric_device')->where($where)->data($data)->save();
            if($device_id){
                $this->success('修改设备成功！', U('index'));
            }else{
                $this->error('修改设备失败！');
            }
		}else{
			$where = [];
			$where['village_id'] = $this->house_session['village_id'];
			$where['id'] = $_GET['id'];
			
			$now_device = M('House_electric_device')->where($where)->find();
			$this->assign('now_device', $now_device);
		
			$this->display();
		}
	}
	
	public function del(){
		if(empty($_POST['id'])){
			$this->error('设备ID必传');
		}
		
		$where = [];
		$where['id']	 = trim($_POST['id']);
		$where['village_id'] = $this->house_session['village_id'];
		
		$device_id = M('House_electric_device')->where($where)->delete();
		if($device_id){
			$this->success('删除设备成功！', U('index'));
		}else{
			$this->error('删除设备失败！');
		}
		
	}
	
	public function quick_open_door(){
		$data = [
			'device_id' => $_POST['device_id'],
			'cmd' 		=> 'open',
			'status	' 	=> '0',
			'add_time' 	=> time(),
			
		];
		
		$result = M('House_electric_msg')->data($data)->add();
		
		if ($result) {
            $array = array(
                'errorCode'=>0,
                'errorMsg'=> '添加成功'
            );
        } else {
            $array = array(
                'errorCode'=>1,
                'errorMsg'=> '添加失败'
            );
        }
		exit(json_encode($array));
	}
}
?>