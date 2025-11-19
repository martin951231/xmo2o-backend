<?php
/*
 * 搜索关键词
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/06 16:47
 * 
 */
class SearchhotAction extends BaseAction{
	public function index(){
		$database_search_hot = D('Search_hot');
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
		$search_hot_list = $database_search_hot->where($condition)->field(true)->order('`sort` DESC,`id` ASC')->select();
		$many_city = $this->config['many_city'];
		if ($many_city == 1 && $search_hot_list) {
            foreach ($search_hot_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
            }
        }
		$this->assign('search_hot_list',$search_hot_list);
		
		$this->display();
	}
	public function add(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	public function modify(){
		if(IS_POST){
			if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
				$_POST['province_id']	=	0;
				$_POST['city_id']	=	0;
			}else{
				$_POST['province_id']	=	$_POST['province_idss'];
				$_POST['city_id']	=	$_POST['city_idss'];
				unset($_POST['province_idss'],$_POST['city_idss']);
			}
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			$database_search_hot = D('Search_hot');
			if($database_search_hot->data($_POST)->add()){
				S('search_hot_list',NULL);
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function edit(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		$database_search_hot = D('Search_hot');
		$condition_search_hot['id'] = intval($_GET['id']);
		$search_hot = $database_search_hot->field(true)->where($condition_search_hot)->find();
		$this->assign('search_hot',$search_hot);
		$this->display();
	}
	public function amend(){
		if(IS_POST){
			if($_POST['currency'] == 1){
				$_POST['province_id']	=	0;
				$_POST['city_id']	=	0;
			}else{
				$_POST['province_id']	=	$_POST['province_idss'];
				$_POST['city_id']	=	$_POST['city_idss'];
				unset($_POST['province_idss'],$_POST['city_idss']);
			}
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			$database_search_hot = D('Search_hot');
			if($database_search_hot->data($_POST)->save()){
				S('search_hot_list',NULL);
				$this->success('修改成功！');
			}else{
				$this->error('修改失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function del(){
		if(IS_POST){
			$database_search_hot = D('Search_hot');
			$condition_search_hot['id'] = intval($_POST['id']);
			if($database_search_hot->where($condition_search_hot)->delete()){
				S('search_hot_list',NULL);
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
}