<?php
/*
 * 城市区域管理
 *
 */

class AreaAction extends BaseAction{
    public function index(){
// 		$database_system_menu = D('System_menu');
// 		$condition_system_menu['status'] = 1;
// 		$menu_list = $database_system_menu->field(true)->where($condition_system_menu)->order('`fid` ASC,`id` ASC')->select();
// 		foreach($menu_list as $key=>$value){
// 			if($value['fid'] == 0){
// 				$system_menu[$value['id']] = $value;
// 			}else{
// 				$system_menu[$value['fid']]['menu_list'][] = $value;
// 			}
// 		}
// 		$this->assign('system_menu',$system_menu);
		
		$this->display();
    }
	public function ajax_province(){
		$database_area = D('Area');
		$condition_area['area_type'] = 1;
		$condition_area['is_open'] = 1;
		$province_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		if(!empty($province_list)){
			$return['error'] = 0;
			$return['list'] = $province_list;
		}else{
			$return['error'] = 1;
			$return['info'] = '没有已开启的省份！';
		}
		exit(json_encode($return));
	}
	public function ajax_city(){
		if (empty($_POST['id'])) {
			$return['error'] = 0;
			$return['list'] = array();
			exit(json_encode($return));
		}
		$database_area = D('Area');
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$city_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		if(!empty($city_list)){
			$return['error'] = 0;
			$return['list'] = $city_list;
		}else{
			$return['error'] = 1;
			$return['info'] = '［' . $_POST['name'] . '］省份下没有已开启的城市！';
		}
		exit(json_encode($return));
	}
	public function ajax_area(){
		if (empty($_POST['id'])) {
			$return['error'] = 0;
			$return['list'] = array();
			exit(json_encode($return));
		}
		$database_area = D('Area');
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$area_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		if(!empty($area_list)){
			$return['error'] = 0;
			$return['list'] = $area_list;
		}else{
			$return['error'] = 1;
			$return['info'] = '［' . $_POST['name'] . '］ 城市下没有开启了的区域！';
		}
		exit(json_encode($return));
	}
	public function ajax_circle(){
		$database_area = D('Area');
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$circle_list = $database_area->field('`area_id` `id`,`area_name` `name`,`first_pinyin`')->where($condition_area)->order('`area_sort` DESC,`first_pinyin` ASC')->select();
		if(!empty($circle_list)){
			$tmp_list = array();
			foreach($circle_list as $key=>$value){
				if(empty($tmp_list[$value['first_pinyin']])){
					$circle_list[$key]['name'] = $value['first_pinyin'].'. '.$value['name'];
					$tmp_list[$value['first_pinyin']] = true;
				}else{
					$circle_list[$key]['name'] = '&nbsp;&nbsp;&nbsp;'.$value['name'];
				}
			}
			$return['error'] = 0;
			$return['list'] = $circle_list;
		}else{
			$return['error'] = 1;
			$return['info'] =  '［' . $_POST['name'] . '］区域下没有已开启的商圈！请联系管理员添加';
		}
		exit(json_encode($return));
	}
    // 获取 街道/乡镇
    public function ajax_street()
    {
		if (empty($_POST['id'])) {
			$return['error'] = 0;
			$return['list'] = array();
			exit(json_encode($return));
		}
        $database_area = D('Area_street');
        $condition_area['area_pid'] = intval($_POST['id']);
        $condition_area['is_open'] = 1;
        $condition_area['area_type'] = 0;
        $area_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［' . $_POST['name'] . '］ 城市区域下没有已开启的街道/乡镇！请先开启街道/乡镇或删除此城市区域';
        }
        exit(json_encode($return));
    }

    public function ajax_community(){
		if (empty($_POST['id'])) {
			$return['error'] = 0;
			$return['list'] = array();
			exit(json_encode($return));
		}
        $database_area = D('Area_street');
        $condition_area['area_pid'] = intval($_POST['id']);
        $condition_area['is_open'] = 1;
        $condition_area['area_type'] = 1;
        $area_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] =  '［' . $_POST['name'] . '］街道/乡镇下没有已开启的社区/村！请先开启社区/村或删除此街道/乡镇';
        }
        exit(json_encode($return));
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
}