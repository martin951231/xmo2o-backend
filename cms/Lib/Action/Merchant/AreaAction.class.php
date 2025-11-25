<?php
/*
 * 城市区域管理
 *
 */

class AreaAction extends BaseAction{
    public function index(){
		$database_system_menu = D('System_menu');
		$condition_system_menu['status'] = 1;
		$menu_list = $database_system_menu->field(true)->where($condition_system_menu)->order('`fid` ASC,`id` ASC')->select();
		foreach($menu_list as $key=>$value){
			if($value['fid'] == 0){
				$system_menu[$value['id']] = $value;
			}else{
				$system_menu[$value['fid']]['menu_list'][] = $value;
			}
		}
		$this->assign('system_menu',$system_menu);

		$this->display();
    }
	public function getLocation(){
		$return['many_city'] = false;
		if($this->config['many_city']){
			//演示站直接使用 合肥
			if($this->config['is_demo_domain']){
				$city = L_('安徽省合肥市');
			}else{
				//通过IP得到当前IP的地理位置
				import('ORG.Net.IpLocation');
				$Ip = new IpLocation('UTFWry.dat');
				$area = $Ip->getlocation();
				$city = iconv('gbk','utf-8',$area['country']);
			}
			
			//判断数据库里存不存在当前的城市

			$condition_now_city['area_type'] = '2';
			$condition_now_city['area_ip_desc'] = $city;
			$condition_now_city['is_open'] = '1';
			$now_city = D('Area')->field('`area_id`,`area_name`,`area_pid`')->where($condition_now_city)->find();

			if(!empty($now_city)){
				$return['city'] = $now_city;
			}
			
			$return['many_city'] = true;
		}
		if(empty($return['city'])){
			$return['city'] = D('Area')->get_area_by_areaId($this->config['now_city']);
		}
		
		$return['province'] = D('Area')->get_area_by_areaId($return['city']['area_pid']);
		
		$return = array(
			'city_id' => $return['city']['area_id'],
			'city_name' => $return['city']['area_name'],
			'province_id' => $return['province']['area_id'],
			'province_name' => $return['province']['area_name'],
			'many_city' => $return['many_city'],
		);
		
		$this->success($return);
	}
	public function ajax_province(){
		$database_area = D(_view('Area'));
		$condition_area['area_type'] = 1;
		if($_GET['open_limit']){//不受开启状态的限制（2应该表示已删除）
			$condition_area['is_open'] = array('neq',2);
		}else{
			$condition_area['is_open'] = 1;//开启状态
		}
		$data = $database_area->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		$province_list = array();
		foreach ($data as $key => $value) {
			$temp = array(
				'id' => $value['area_id'],
				'name' => $value['area_name']
			);
			$province_list[] = $temp;
		}
		if(count($province_list) == 1){
			$return['error'] = 2;
			$return['id'] = $province_list[0]['id'];
			$return['name'] = $province_list[0]['name'];
		}else if(!empty($province_list)){
			$return['error'] = 0;
			$return['list'] = $province_list;
		}else{
			$return['error'] = 1;
			$return['info'] = L_('没有已开启的省份！');
		}
		exit(json_encode($return));
	}
	public function ajax_city(){
		$database_area = D(_view('Area'));
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$data = $database_area->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		$city_list = array();
		foreach ($data as $key => $value) {
			$temp = array(
				'id' => $value['area_id'],
				'name' => $value['area_name']
			);
			$city_list[] = $temp;
		}
		if(count($city_list) == 1 && !$_POST['type']){
			$return['error'] = 2;
			$return['id'] = $city_list[0]['id'];
			$return['name'] = $city_list[0]['name'];
		}else if(!empty($city_list)){
			$return['error'] = 0;
			$return['list'] = $city_list;
		}else{
			$return['error'] = 1;
			$return['info'] = $_POST['name'] .' 省份下没有已开启的城市！';
		}
		exit(json_encode($return));
	}
	public function ajax_area(){
		$database_area = D(_view('Area'));
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$data = $database_area->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		$area_list = array();
		foreach ($data as $key => $value) {
			$temp = array(
				'id' => $value['area_id'],
				'name' => $value['area_name']
			);
			$area_list[] = $temp;
		}
		if(!empty($area_list)){
			$return['error'] = 0;
			$return['list'] = $area_list;
		}else{
			$return['error'] = 1;
			$return['info'] = $_POST['name'] .' 城市下没有开启了的区域！';
		}
		exit(json_encode($return));
	}

    public function ajax_street()
    {
        $database_street = D('Area_street');
        $condition_area['area_pid'] = intval($_POST['id']);
        $condition_area['is_open'] = 1;
        $data = $database_street->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        $area_list = array();
        foreach ($data as $key => $value) {
            $temp = array(
                'id' => $value['area_id'],
                'name' => $value['area_name']
            );
            $area_list[] = $temp;
        }
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = $_POST['name'] . ' 城市下没有开启了的区域！';
        }
        exit(json_encode($return));
    }

	public function ajax_circle(){
		$database_area = D(_view('Area'));
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$data = $database_area->field(true)->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		$circle_list = array();
		foreach ($data as $key => $value) {
			$temp = array(
				'id' => $value['area_id'],
				'name' => $value['area_name']
			);
			$circle_list[] = $temp;
		}
		if(!empty($circle_list)){
			$tmp_list = array();
			foreach($circle_list as $key=>$value){
				// if(empty($tmp_list[$value['first_pinyin']])){
				// 	$circle_list[$key]['name'] = $value['first_pinyin'].'. '.$value['name'];
				// 	$tmp_list[$value['first_pinyin']] = true;
				// }else{
					$circle_list[$key]['name'] = '&nbsp;&nbsp;&nbsp;'.$value['name'];
				// }
			}
			$return['error'] = 0;
			$return['list'] = $circle_list;
		}else{
			$return['error'] = 1;
			$return['info'] = $_POST['name'] .' 区域下没有已开启的商圈！请联系管理员添加';
		}
		exit(json_encode($return));
	}
	public function ajax_market(){
		$database_area = D('Area_market');
		$condition_area['area_id'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$circle_list = $database_area->field('`market_id` `id`,`market_name` `name`')->where($condition_area)->select();
		if(!empty($circle_list)){
			$return['error'] = 0;
			$return['list'] = $circle_list;
		}else{
			$return['error'] = 1;
			$return['info'] = $_POST['name'] .' 商圈下没有已开启的商场！请联系管理员添加';
		}
		exit(json_encode($return));
	}
}