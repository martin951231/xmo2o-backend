<?php

/*
 * 跑腿优惠时段
 * @time  2021/09/13 16:08
 * 
 */

class Deliver_time_discountAction extends BaseAction {
	protected $deliver_user, $deliver_store, $deliver_location, $deliver_supply;
	
	protected function _initialize() {
		parent::_initialize();
		$this->deliver_user = D("Deliver_user");
		$this->deliver_store = D("Deliver_store");
		$this->deliver_location = D("Deliver_location");
		$this->deliver_supply = D("Deliver_supply");
	}

    public function index(){
       
        $where = [
            'is_del' => 0
        ];
        $count = M('Service_time_discount')->where($where)->count();

        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $list = M('Service_time_discount')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach($list as &$value){
            $value['start_time'] = date('H:i', strtotime(date('Y-m-d').' '.$value['start_time']));
            $value['end_time'] = date('H:i', strtotime(date('Y-m-d').' '.$value['end_time']));
        }
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
        
    }


	public function add(){
        if(IS_POST){
            if($_POST['start_time'] == '00:00' && $_POST['end_time'] == '00:00'){
                $this->error('请选择时间段');
            }

            if($_POST['start_time'] == $_POST['end_time']){
                $this->error('结束时间不能等于开始时间');
            }

            // 时间段查重
            $where_check['is_del'] = 0;
            $where_check['_string'] = '(start_time >= "'.$_POST['start_time'].'" AND start_time <= "'.$_POST['end_time'].'") OR start_time = "'.$_POST['start_time'].'"  OR start_time = "'.$_POST['end_time'].'" OR end_time = "'.$_POST['start_time'].':59" OR end_time = "'.$_POST['end_time'].':59"';
            if(M('Service_time_discount')->where($where_check)->find()){
                $this->error('时间段重叠，请重新设置');
            }
            $_POST['start_time'] = $_POST['start_time'].':00';
            $_POST['end_time'] = $_POST['end_time'].':59';
            $_POST['add_time'] = time();
            $res = M('Service_time_discount')->data($_POST)->add();
            if($res === false){
                $this->error('添加失败');
            }
            $this->success('添加成功', U('index'));
        }else{
            $this->display();
        }
    }


    public function edit(){
        if(IS_POST){
            $discount_id =  $_POST['discount_id'];

            $where = ['discount_id'=>$discount_id,'is_del'=>0];
            $time_discount =  M('Service_time_discount')->where($where)->find();
            if(empty($time_discount)){
                $this->error('数据不存在或已删除');
            }

            if($_POST['start_time'] == '00:00' && $_POST['end_time'] == '00:00'){
                $this->error('请选择时间段');
            }

            if($_POST['start_time'] == $_POST['end_time']){
                $this->error('结束时间不能等于开始时间');
            }

            // 时间段查重
            $where_check['discount_id'] = ['neq', $discount_id];
            $where_check['is_del'] = 0;
            $where_check['_string'] = '(start_time >= "'.$_POST['start_time'].'" AND start_time <= "'.$_POST['end_time'].'") OR start_time = "'.$_POST['start_time'].'"  OR start_time = "'.$_POST['end_time'].'" OR end_time = "'.$_POST['start_time'].':59" OR end_time = "'.$_POST['end_time'].':59"';
            if(M('Service_time_discount')->where($where_check)->find()){
                $this->error('时间段重叠，请重新设置');
            }

            $_POST['start_time'] = $_POST['start_time'].':00';
            $_POST['end_time'] = $_POST['end_time'].':59';
            
            
            $res = M('Service_time_discount')->where($where)->data($_POST)->save();
            if($res === false){
                $this->error('编辑失败');
            }
            $this->success('编辑成功', U('index'));
        }else{
            

            $discount_id =  $_GET['discount_id'];

            $where = ['discount_id'=>$discount_id,'is_del'=>0];
            $time_discount =  M('Service_time_discount')->where($where)->find();
            if(empty($time_discount)){
                $this->error('数据不存在或已删除');
            }
            $time_discount['start_time'] = date('H:i', strtotime(date('Y-m-d').' '.$time_discount['start_time']));
            $time_discount['end_time'] = date('H:i', strtotime(date('Y-m-d').' '.$time_discount['end_time']));
            $this->assign('time_discount',$time_discount);
            $this->display();
        }
    }


    public function del(){
        $discount_id =  $_POST['discount_id'];

        $where = ['discount_id'=>$discount_id,'is_del'=>0];
        $time_discount =  M('Service_time_discount')->where($where)->find();
        if(empty($time_discount)){
            $this->error('数据不存在或已删除');
        }

        $res =  M('Service_time_discount')->where($where)->setField('is_del',1);

        if($res == false){
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }

    // 区域优惠时段配置
    public function area_set(){
        $where = ['is_open'=>'1','area_type'=>'2'];
        
        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            if ($area_index == 'province_id') {
                $where['area_pid'] = $this->system_session['area_id'];
            } elseif ($area_index == 'city_id') {
                $where['area_id'] = $this->system_session['area_id'];
            } else {
                die('城市管理员及以上才能设置调度模式');
            }
        }
        
        if($_GET['searchtype'] == 'area_name'){
            $where['area_name'] = ['like','%'.trim($_GET['keyword']).'%'];
        }
        $city_list = M('Area')->where($where)->order('`area_sort` DESC')->select();

        foreach($city_list as &$_city){

            $where = ['city_id'=>$_city['area_id']];
            $discount_bind =  M('Service_time_discount_bind_area')->where($where)->select();
            $_city['discount_ids'] = array_column($discount_bind,'discount_id');
        }

        // 查询优惠列表
        $where = ['is_del'=>0];
        $discount_list =  M('Service_time_discount')->where($where)->order('add_time asc')->select();
        $this->assign('discount_list',$discount_list);

        $this->assign('city_list',$city_list);

        $this->display();
    }

    // 设置是否开启优惠时段
    public function ajax_change_open(){

        $area_id = $_POST['area_id'];
        $status = $_POST['status'];
        
        if(empty($area_id)){
            $this->error('缺少参数');
        }

        $where = ['area_id'=>$area_id];
        $area = M('Area')->where($where)->find(); 
        if(empty($area)){
            $this->error('数据不存在或已删除');
        }

        
        $res = M('Area')->where($where)->setField('open_deliver_time_discount', $status);
        if($res == false){
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }

    // 区域选择优惠时段
    public function ajax_select_discount(){

        $city_id = $_POST['city_id'];
        $discount_id_arr = $_POST['discount_id_arr'];
        
        if(empty($city_id)){
            $this->error('缺少参数');
        }

        $where = ['area_id'=>$city_id];
        $area = M('Area')->where($where)->find(); 
        if(empty($area)){
            $this->error('数据不存在或已删除');
        }

        $where = ['city_id'=>$city_id];
        $res =  M('Service_time_discount_bind_area')->where($where)->delete();

        foreach($discount_id_arr as $discount_id){
            $data[] = [
                'city_id' => $city_id,
                'discount_id' => $discount_id,
            ];
        }

        $res =  M('Service_time_discount_bind_area')->addAll($data);
        if($res == false){
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }

}
?>