<?php
/*
 * 导航管理
 *
 * @  Writers    Go
 * @  BuildTime  2014/12/02 9:43
 * 
 */
class Wap_aroundAction extends BaseAction{
	public function alise_name(){
		return array(
				'group'=>$this->config['group_alias_name'],
				'shop'=>$this->config['shop_alias_name'],
				'meal'=>$this->config['meal_alias_name'],
				'appoint'=>$this->config['appoint_alias_name'],
				'merchant'=>'商家',
		);
	}
	public function index(){
		$condition = array();
		if ($this->system_session['area_id']) {
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($temp['area_type'] == 1){
					$city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'],true);
					$area_id = array();
					foreach($city_list as $value){
						$area_id[] = $value['area_id'];
					}
				}else if($temp['area_type'] == 2){
					$area_id = $temp['area_id'];
				}else{
					$area_id = $temp['area_pid'];
				}
            }
			if(is_array($area_id)){
				$condition['city_id'] = array('in',$area_id);
			}else{
				$condition['city_id'] = $area_id;
			}
        }
		$database_wap = D('Wap_around');
		$list = $database_wap->where($condition)->field(true)->order('`sort` DESC,`id` ASC')->select();
		$many_city = $this->config['many_city'];
		if ($many_city == 1 && $list) {
            foreach ($list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
            }
        }
		$this->assign('list',$list);
		$this->assign('alias_name',$this->alise_name());

		$this->display();
	}
	public function add(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	public function modify(){
		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'wap');
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/wap/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['msg']);
			}
		}
		if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
			$_POST['province_id']	=	$_POST['province_idss'];
			$_POST['city_id']	=	$_POST['city_idss'];
			unset($_POST['province_idss'],$_POST['city_idss']);
		}
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		$database_wap = D('Wap_around');
		if($id = $database_wap->data($_POST)->add()){
			D('Image')->update_table_id('/upload/wap/' . $_POST['pic'], $id, 'wap');
			$this->frame_submit_tips(1,'添加成功！');
		}else{
			$this->frame_submit_tips(0,'添加失败！请重试~');
		}
	}
	public function edit(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		
		$database_wap = D('Wap_around');
		$condition_wap['id'] = $_GET['id'];
		$wap_around = $database_wap->field(true)->where($condition_wap)->find();
		if(empty($wap_around)){
			$this->frame_error_tips('该导航不存在！');
		}
		$this->assign('wap_around',$wap_around);
		$this->assign('alias_name',$this->alise_name());
		$this->display();
	}
	
	public function amend(){
		$database_wap = D('Wap_around');
		$condition_wap['id'] = $_POST['id'];
		$now_wap = $database_wap->field(true)->where($condition_wap)->find();
		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'wap');
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/wap/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['msg']);
			}
		}
		if($_POST['currency'] == 1){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
			$_POST['province_id']	=	$_POST['province_idss'];
			$_POST['city_id']	=	$_POST['city_idss'];
			unset($_POST['province_idss'],$_POST['city_idss']);
		}
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		if($database_wap->where(array('id'=>$_POST['id']))->data($_POST)->save()){
			D('Image')->update_table_id('/upload/wap/' . $_POST['pic'], $_POST['id'], 'wap');
			if($_POST['pic']){
				if(strpos($now_wap['pic'],'2014/') === false){
					unlink('./upload/wap/'.$now_wap['pic']); 
				}
			}
			$this->frame_submit_tips(1,'编辑成功！');
		}else{
			$this->frame_submit_tips(0,'编辑失败！请重试~');
		}
	}
	
	public function del(){
		$database_wap = D('Wap_around');
		$condition_wap['id'] = $_POST['id'];
		$now_wap = $database_wap->field(true)->where($condition_wap)->find();
		if($database_wap->where($condition_wap)->delete()){
			if($now_wap['pic']){
				if(strpos($now_wap['pic'],'2014/') === false){
					unlink('./upload/wap/'.$now_wap['pic']); 
				}
			}
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

	public function hide(){
		$hide = $_POST['hide'];
		M('Config')->where(array('name'=>'wap_around_show_type'))->setField('value',$hide);
	}

}