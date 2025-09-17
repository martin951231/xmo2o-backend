<?php

//社区O2O的电话

class HousephoneAction extends BaseAction{
	public function index(){
		$base_url = D('House_village')->base_url;
        if (isset($_GET['property_id']) && $_GET['property_id']>0 && !$_GET['village_id']) {
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'villagelist',
                'isAll' => true,
                'urlParam' => 'property_id='.$_GET['property_id'].'&referer_type=house_phone',
            ];
            $villagelistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            redirect($villagelistUrl);
        }
		$base_url = D('House_village')->base_url;
		$referer_url = $base_url . 'pages/village/commonly_used/house_phone?village_id='.$_GET['village_id'] . ($_GET['pigcms_id'] ? '&pigcms_id=' . $_GET['pigcms_id'] : '');
		$url = $this->config['site_url'].$referer_url;
		redirect($url);

		$now_village = $this->get_village($_GET['village_id']);
		$this->assign('now_village',$now_village);
		$phone_list = D('House_village_phone_category')->getAllCatPhoneList($now_village['village_id']);
		$this->assign('phone_list',$phone_list);
		// dump($now_village);
		$this->display();
	}
	protected function get_village($village_id){
		$now_village = D('House_village')->get_one($village_id);
		if(empty($now_village)){
			$this->error_tips('当前访问的'.C('config.house_name').'不存在或未开放');
		}
		$this->assign('now_village',$now_village);
		return $now_village;
	}
}

?>