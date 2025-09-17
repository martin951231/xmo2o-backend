<?php
/*
 * 店铺状态
 *
 * @  Writers    lumn
 * @  hark定制需求
 * 
 */
class StorestateAction extends BaseAction{
	public $second = 60;//多少秒请求一次后台 
	public function index(){
		$db = M("Merchant_store");
		$where = [];
		$where['a.status'] = array('not in',[0,4]);

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'a.area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'a.city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'a.province_id';
			}
			$this->assign('admin_area',$now_area['area_type']);
			// $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
			$where[$area_index] = $this->system_session['area_id'];
            if($this->system_session['street_id']){
                $where['street_id'] = $this->system_session['street_id'];
            }
		}
		if($_GET['province_idss'] && $this->config['many_city']){
			$where['a.province_id'] =$_GET['province_idss'];
		}
		if($_GET['city_idss']){
			$where['a.city_id'] = $_GET['city_idss'];
		}
		if($_GET['area_id']){
			$where['a.area_id'] = $_GET['area_id'];
		}

		$searchtype = I("searchtype");
		$keyword = trim(I("keyword"));
		if($keyword){
			switch ($searchtype) {
				case 'storeid':
					$where['a.store_id'] = $keyword;
					break;
				case 'storename':
					$where['a.name'] = ['like', '%'.$keyword.'%'];
					break;
			}
		}
		$searchstatus = I("searchstatus");
		switch ($searchstatus) {
			case '1':
				$where['a.have_shop'] = 1;
				$where['b.is_close'] = 0;
				break;
			case '2':
				$where['a.have_shop'] = 0;
				break;
			case '3':
				$where['a.have_shop'] = 1;
				$where['b.is_close'] = 1;
				break;
		}
		$yystatus = I("yystatus");
		switch ($yystatus) {
			case '1':
				$where['a.is_business_open'] = 1;
				break;
			case '2':
				$where['a.is_business_open'] = 0;
				break;
		}
		$slang = I("lang");
		if($slang){
			$where['c.merchant_default_lang'] = $slang;
		}
		
		$notice = I("notice");
		switch ($notice) {
			case '1':
				$where['a.staff_notice_count'] = ['gt',0];
				break;
			case '2':
				$where['a.staff_notice_count'] = 0;
				break;
		}
		
		$last_time = I("last_time");
		switch ($last_time) {
			case '1':
				$where['a.staff_last_time'] = ['egt',time() - 60];
				break;
			case '2':
				$where['a.staff_last_time'] = ['lt',time() - 60];
				break;
		}
        $where['c.status'] = 1;
		$count = $db->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store_shop as b on a.store_id=b.store_id")->join("inner join ".C("DB_PREFIX")."merchant as c on a.mer_id=c.mer_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count,20);
		$data = $db->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store_shop as b on a.store_id=b.store_id")->join("left join ".C("DB_PREFIX")."merchant as c on a.mer_id=c.mer_id")->field("a.*,b.is_close,c.merchant_default_lang")->where($where)->order('a.`sort` DESC,a.`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();

		$areas = array_unique(array_merge(array_column($data, 'city_id'),array_column($data, 'area_id')));
		$areas_data = [];
		if($areas){
			$areas_data = array_column(M("Area")->where(['area_id'=>['in',implode(',',$areas)]])->field("area_id, area_name")->select(), 'area_name', 'area_id');
		}
		$langs = M("Lang_service")->select();
		$langs_data = array_column($langs, 'display', 'val');

		foreach ($data as $key => $value) {
			$data[$key]['city'] = $areas_data[$value['city_id']] ? : '--';
			$data[$key]['area'] = $areas_data[$value['area_id']] ? : '--';
			$data[$key]['lang'] = $langs_data[$value['merchant_default_lang']] ? : '--';
			$data[$key]['nums'] = $value['staff_notice_count'];
			$data[$key]['jd_status'] = $value['staff_notice_count'] > 0 ? 1 : 0;
			$data[$key]['tx_status'] = $value['staff_last_time'] > time() - 60  ? 1 : 0;
		}

		$all_count = [
			'count' => $count,
			'have_shop' => 0,
			'no_business' => 0
		];
		//外卖店铺数
		$have_shop = $where;
		// unset($have_shop['a.have_shop'], $have_shop['b.is_close']);
		$have_shop['a.have_shop'] = 1;
		$all_count['have_shop'] = $db->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store_shop as b on a.store_id=b.store_id")->join("left join ".C("DB_PREFIX")."merchant as c on a.mer_id=c.mer_id")->where($have_shop)->count();

		//未营业数
		if($yystatus == 1){
			$all_count['no_business'] = 0;
		}
		else{
			$no_business = $where;
			$no_business['a.is_business_open'] = 0;
			// $no_business['a.have_shop'] = 1;
			$all_count['no_business'] = $db->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store_shop as b on a.store_id=b.store_id")->join("left join ".C("DB_PREFIX")."merchant as c on a.mer_id=c.mer_id")->where($no_business)->count();
		}

		$this->assign("all_count", $all_count);
		$this->assign('store_list',$data);
		$pagebar = $p->show();

		$this->assign("langs", $langs);
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	public function checkStoreStatus($store_id){
		$where = [
			'store_id' => $store_id,
		];
		$data = M('Merchant_store_staff')->where($where)->field("is_notice,last_time")->select();
		$return = [
			'all' => 0,//总共有多少人接单
			'pinpang' => 0 //一共有几人能够连接心跳
		];
		foreach ($data as $key => $value) {
			if($value['is_notice'] == '0' && $value['last_time'] > 0){
				$return['all']++;
			}
			if($value['last_time']+$this->second > time()){
				$return['pinpang']++;
			}
		}
		return $return;
	}

	public function staff_list(){
		$store_id = I("store_id", 'intval');
		if(empty($store_id)) die();
		$where = [
			'store_id' => $store_id
		];
		$data = M('Merchant_store_staff')->where($where)->order('`last_time` DESC,`id` ASC')->select();
		foreach ($data as $key => $value) {
			$data[$key]['is_login'] = $value['last_time'] > 0 ? 1 : 0;//是否正在登录
			$data[$key]['pingpang'] = $value['last_time'] + $this->second > time() ? 1 : 0;//1=正常 0=异常
		}
		$this->assign("staff_list", $data);
		$this->display();
	}

	public function staff_log(){
		$staff_id = I("staff_id", 'intval');
		$staff_account = I("account");
		if(empty($staff_id)) die();
		$where = [
			'uid' => $staff_id
		];
		$db = M("Store_staff_login_log");
		$count = $db->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count,20);
		$data = $db->where($where)->order("add_time desc")->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('log_list',$data);
		$this->assign("staff_account",$staff_account);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

}
