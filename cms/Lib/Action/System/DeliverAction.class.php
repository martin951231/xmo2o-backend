<?php

/*
 * //用户中心
 *
 * @  Writers    yanleilei
 * @  BuildTime  2015/8/18 18:25
 * 
 */

class DeliverAction extends BaseAction {
	protected $deliver_user, $deliver_store, $deliver_location, $deliver_supply;
	
	protected function _initialize() {
		parent::_initialize();
		$this->deliver_user = D("Deliver_user");
		$this->deliver_store = D("Deliver_store");
		$this->deliver_location = D("Deliver_location");
		$this->deliver_supply = D("Deliver_supply");
	}
	
	public function config(){
		redirect(U('Config/index',array('galias'=>'deliver','header'=>'Deliver/header')));
	}

    public function config_level(){
        if(IS_POST){
            $database_config = D('Config');
            foreach($_POST as $key=>$value){
                $data['name'] = $key;
                $data['value'] = trim(stripslashes(htmlspecialchars_decode($value)));

                $num = $database_config->where(array('name'=>$key))->count();
                if($num>0){
                    $database_config->data($data)->save();
                }else{
                    $database_config->data($data)->add();
                }
            }
            S('system_config',null);

            $this->success('编辑成功');
        }else{
            $condition_config['gid'] = 105;
            $condition_config['status'] = '1';
            $config_list = D('Config')->where($condition_config)->order('`sort` DESC')->select();
            foreach ($config_list as $value) {
                if ($value['name'] == 'deliver_level_down_days') {
                   $deliver_level_down_days = $value['value'];
                }
                if ($value['name'] == 'deliver_level_down_num') {
                   $deliver_level_down_num = $value['value'];
                }
                if ($value['name'] == 'deliver_level_down_score') {
                   $deliver_level_down_score = $value['value'];
                }
            }
            
            $this->assign('deliver_level_down_days',$deliver_level_down_days);
            $this->assign('deliver_level_down_num',$deliver_level_down_num);
            $this->assign('deliver_level_down_score',$deliver_level_down_score);
            $this->assign('config_list',$config_list);
            $this->display();
        }
    }

        
    /**
     * 配送员注册自定义字段列表
     */
    public function field() {
        $database_deliver_user_field = D('Deliver_user_field');
		$now_category = $database_deliver_user_field->select();
		$this->assign('now_category',$now_category);
		$this->display();
    }

    // 预约自定义表单添加字段
	public function field_add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}

        
    /**
     * 配送员注册自定义字段操作
     */
    public function field_modify() {
    	if(IS_POST){
			$database_deliver_user = D('Deliver_user_field');
			$now_category = $database_deliver_user->select();
			
            foreach($now_category as $key=>$value){
                if($value['name'] == $_POST['name']){
                    $this->error('该填写项已经添加，请勿重复添加！');
                }
            }

			$post_data['name'] = $_POST['name'];
			$post_data['type'] = $_POST['type'];
			$post_data['sort'] = strval($_POST['sort']);
			$post_data['iswrite'] = $_POST['iswrite'];
            empty($_POST['use_field']) ? '' : $post_data['use_field'] = $_POST['use_field'];
            if(!empty($_POST['use_field'])){
                $post_data['use_field'] = serialize(explode(PHP_EOL, $_POST['use_field']));
            }
			if($database_deliver_user->data($post_data)->add()){
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
    }

        
    /**
     * 配送员注册自定义字段删除
     */
    public function field_del(){
		if(IS_POST){
			$database_group_category = D('Deliver_user_field');
			$condition_now_group_category['name'] = $_POST['name'];

			if(empty($now_category = $database_group_category->where($condition_now_group_category)->find())){
				$this->error('此填写项不存在！');
			}
			if($database_group_category->where(['name'=>$_POST['name']])->delete()){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	
	/**
	 * 配送员列表
	 */
	public function user($isReturn = false) {
	    //搜索
	    if (!empty($_GET['keyword'])) {
	        if ($_GET['searchtype'] == 'uid') {
	            $condition_user['uid'] = $_GET['keyword'];
	        } else if ($_GET['searchtype'] == 'nickname') {
	            $condition_user['name'] = array('like', '%' . $_GET['keyword'] . '%');
	        } else if ($_GET['searchtype'] == 'phone') {
	            $condition_user['phone'] = array('like', '%' . $_GET['keyword'] . '%');
	        }
	    }
        if ($_GET['province_idss']) {
            $condition_user['province_id'] = $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $condition_user['city_id'] = $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $condition_user['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_id']) {
            $condition_user['street_id'] = $_GET['street_id'];
        }
        if (isset($_GET['register_type']) && $_GET['register_type'] >= 0) {
            $condition_user['register_type'] = $_GET['register_type'];
        }
	    $condition_user['group'] = 1;
        $condition_user['status'] = array('neq',4);
        if (isset($_GET['status']) && in_array($_GET['status'], [0, 1])) {
            $condition_user['status'] = intval($_GET['status']);
        }

        $currentStatus = I('get.current_status', -1, 'intval');
        if ($currentStatus == 0) {
            $condition_user['last_time'] = 0;
        } else if ($currentStatus == 1) {
            $condition_user['last_time'] = ['gt', 0];
        }

		$menus = $this->system_session['menus'];
		$show_merchant_user = 0;
		if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
		$this->assign('menus',$menus);

	    if ($this->system_session['area_id']) {
			$area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
			$condition_user[$area_index] = $this->system_session['area_id'];
		}
        if ($this->system_session['street_id']) {
            $condition_user['street_id'] = $this->system_session['street_id'];
        }
	    $count_user = $this->deliver_user->where($condition_user)->count();
        if ($isReturn) {
            $user_list = $this->deliver_user->field(true)->where($condition_user)->order('`status` DESC, `sort` DESC')->select();
        } else {
            import('@.ORG.system_page');
            $p = new Page($count_user, 20);
            $user_list = $this->deliver_user->field(true)->where($condition_user)->order('`status` DESC, `sort` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }


	    $m = date('Ym'.'01');
	    $m_start = strtotime($m);
        $next = strtotime("next month");
        $m_end = strtotime(date('Ym'.'01',$next))-1;
        $where['end_time'] = array('between',array($m_start,$m_end));
        $where['status'] = array('gt',4);
	    foreach($user_list as &$user){
	        $where['uid'] = $user['uid'];
	        $month_count = M('Deliver_supply')->where($where)->count();
            $user['month_count'] = $month_count;
            // 当前等级
            if (C('config.open_deliver_level')) {
                $now_level = D('Deliver_user')->get_user_level($user['score']);
                $user['level_name'] = $now_level['name'];
            }
        }
        if ($isReturn) {
            return $user_list;
        }
	    $this->assign('user_list', $user_list);
	    $pagebar = $p->show();
	    $this->assign('pagebar', $pagebar);
        $this->display();
	}

    /**
     * 配送员列表
     */
    public function merchant_user($isReturn = false) {
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['u.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'mer_id') {
                $condition_user['m.name'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        if ($_GET['province_idss']) {
            $condition_user['u.province_id'] = $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $condition_user['u.city_id'] = $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $condition_user['u.area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_id']) {
            $condition_user['u.street_id'] = $_GET['street_id'];
        }
        if (isset($_GET['register_type']) && $_GET['register_type'] >= 0) {
            $condition_user['u.register_type'] = $_GET['register_type'];
        }
        $condition_user['u.group'] = 2;
        
        $menus = $this->system_session['menus'];

        $this->assign('menus',$menus);

        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            $condition_user['u.'.$area_index] = $this->system_session['area_id'];
        }

        $count_user = $this->deliver_user->alias('u')->join('left join '.C('DB_PREFIX').'merchant as m on u.mer_id = m.mer_id')->where($condition_user)->count();
        if ($isReturn) {
            $user_list = $this->deliver_user->alias('u')->field('u.*,m.name as mer_name')->join('left join '.C('DB_PREFIX').'merchant as m on u.mer_id = m.mer_id')->where($condition_user)->order('`status` DESC, `sort` DESC')->select();
        } else {
            import('@.ORG.system_page');
            $p = new Page($count_user, 20);
            $user_list = $this->deliver_user->alias('u')->field('u.*,m.name as mer_name')->join('left join '.C('DB_PREFIX').'merchant as m on u.mer_id = m.mer_id')->where($condition_user)->order('`status` DESC, `sort` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        
        if ($isReturn) {
            return $user_list;
        }
        $this->assign('user_list', $user_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
       
        $this->display('');
        
    }

    /**
     * 骑手在线时长管理
     * @author: 冯磊
     * @date: 2021/9/15
     */
    public function lenTime()
    {
        $params = I('get.');
        //默认30天
        if (empty($params['begin_time'])) {
            $_GET['begin_time'] = $params['begin_time'] = date('Y-m-d', strtotime('-60 days'));
        }
        if (empty($params['end_time'])) {
            $_GET['end_time'] = $params['end_time'] = date('Y-m-d');
        }

        $field = 'dt.*,d.name,d.phone,SUM(dt.working_time) AS total_working_time';
        //筛选
        $where = array('d.status' => ['neq', 4]);
        if(!empty($params['searchtype']) && in_array($params['searchtype'], ['name', 'phone']) && !empty($params['keyword'])){
            $where['d.'.$params['searchtype']] = array('like', '%'.$params['keyword'].'%');
        }

        $where['dt.start_time'] = array('EGT', strtotime($params['begin_time']));
        $where['dt.end_time'] = array('ELT', strtotime($params['end_time'])+86400);

        $logMod = D('Deliver_user_clock_time');
        $count = $logMod->getOnlineTimeCount($where);
        import('@.ORG.system_page');
        $p = new Page($count, 20); 

        $limit = $p->firstRow . ',' . $p->listRows;
        
        $list = $logMod->getOnlineTimeList($field, $where, $limit);
        //数据处理
        $formateTime = function ($sec) {
            if ($sec < 3600) {
                return round($sec / 60, 2) . '分';
            } else {
                return round($sec / 3600, 2) . '时';
            }
        };
        foreach($list as $key => &$val){
            //获取平均工作时间、获取经常上/下班时间，精确到小时
            $rs = $logMod->getDeliverUserSchedule($val['uid'], strtotime($params['begin_time']), strtotime($params['end_time']) + 86399);
            $val['avg_time'] = $formateTime($rs['average_working_time']);
            $val['total_time'] = $formateTime($rs['total_working_time']);
            $val['often_start_time'] = $rs['work_hours'];
            $val['often_end_time'] = $rs['off_duty_hours'];
        }

        $show_merchant_user = 0;
        if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
  
        $pagebar = $p->show();
	    $this->assign('pagebar', $pagebar);
	    $this->assign('list', $list); 
        $this->display();
    }

    /**
     * 骑手在线时长详情
     * @author: 冯磊
     * @date: 2021/9/16
     */
    public function lenTimeDetail()
    {
        $uid = I('get.uid', 0, 'intval');
        $list = D('Deliver_user_clock_time')->getUserOnlineTimeList($uid,200);
        $this->assign('list', $list);
        $this->display(); 
    }

    /**
     * 配送员扫码收单统计
     * @author: 张涛
     * @date: 2020/8/14
     */
    public function scan_report($isReturn = false)
    {
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['u.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        if ($_GET['province_idss']) {
            $condition_user['u.province_id'] = $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $condition_user['u.city_id'] = $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $condition_user['u.area_id'] = $_GET['area_id'];
        }
        $condition_user['u.group'] = 1;
        $condition_user['u.status'] = array('neq', 4);

        $menus = $this->system_session['menus'];

        $this->assign('menus', $menus);

        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            $condition_user['u.' . $area_index] = $this->system_session['area_id'];
        }

        if ($_GET['begin_time']) {
            $condition_user['l.add_time'][] = ['egt', strtotime($_GET['begin_time'])];
        }
        if ($_GET['begin_time']) {
            $condition_user['l.add_time'][] = ['lt', strtotime($_GET['end_time']) + 86400];
        }

        $user_list = D('Scan_order_transfer_log')->alias('l')
            ->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')
            ->join(C('DB_PREFIX') . 'deliver_user AS u ON u.uid = l.uid')
            ->field('u.*')
            ->where($condition_user)
            ->group('u.uid')
            ->order('u.status DESC, u.sort DESC')
            ->select();
 
        $m = date('Ym' . '01');
        $m_start = strtotime($m);
        $next = strtotime("next month");
        $m_end = strtotime(date('Ym' . '01', $next)) - 1;
        $where['end_time'] = array('between', array($m_start, $m_end));
        $where['status'] = array('gt', 4);
        foreach ($user_list as &$user) {
            $this->getCount($user['uid'], $_GET['begin_time'], $_GET['end_time']);
            //记录配送员接单、取餐、配送、送达数量
            list($user['grap_count'], $user['pick_count'], $user['send_count'], $user['complete_count']) = self::getCount($user['uid'], $_GET['begin_time'], $_GET['end_time']);
        }
        if ($isReturn) {
            return $user_list;
        }
        $this->assign('user_list', $user_list);

        $show_merchant_user = 0;
        if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
        
        $this->display();
    }


    /**
     * 获取配送员接单、取餐、配送、送达数量
     * @author 张涛
     * @date 2020/07/13
     */
    public static function getCount($uid, $beginTime, $endTime)
    {
        $where = ['l.uid' => $uid];
        if ($beginTime && $endTime) {
            $where['l.add_time'] = [['egt', strtotime($beginTime)], ['lt', strtotime($endTime) + 86400]];
        }
        $where['l.status'] = 2;
        $grabCount = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
        $where['l.status'] = 3;
        $pickCount = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
        $where['l.status'] = 4;
        $sendCount = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
        $where['l.status'] = ['gt', 4];
        $completeCount = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
        return [$grabCount, $pickCount, $sendCount, $completeCount];
    }

    /**
     * 导出
     * @author 张涛
     * @date 2020/07/21
     */
    public function exportUser()
    {
        $data = $this->scan_report(true);
        if ($_GET['begin_time'] && $_GET['end_time']) {
            $sheetTitle = $_GET['begin_time'] . '至' . $_GET['end_time'];
        } else {
            $sheetTitle = '';
        }

        $config = [
            'header' => [
                ['title' => L_('昵称'), 'value_key' => 'name'],
                ['title' => L_('手机号'), 'value_key' => 'phone'],
                ['title' => L_('常驻地址'), 'value_key' => 'site'],
                ['title' => L_('接单'), 'value_key' => 'grap_count'],
                ['title' => L_('取餐'), 'value_key' => 'pick_count'],
                ['title' => L_('配送'), 'value_key' => 'send_count'],
                ['title' => L_('送达'), 'value_key' => 'complete_count'],
            ],
            'body' => $data,
            'file_name' => time(),
            'sheet_title' => $sheetTitle
        ];
        R('System/Shop/download_store_list', ['config' => $config]);
    }

    /**
     * 获取配送员是否有未完成订单
     */
    public function check_deliver_order(){
        if (IS_POST) {
            $uid = intval($_POST['uid']);
            if(!$uid){
                $this->error('请选择要删除的配送员！');
            }
            $condition_user['uid'] = intval($_POST['uid']);
            if ($this->system_session['area_id']) {
                $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
                $condition_user[$area_index] = $this->system_session['area_id'];
            }
            $user = $this->deliver_user->where($condition_user)->find();
            if(!$user){
                $this->error('配送员不存在！');
            }
            $user_order = D('Deliver_supply')->where(['uid'=>$uid,'status'=>array('in',array(2,3,4))])->find();
            if ($user_order) {
                $this->success('该配送员有未完成订单！');
            } else {
                $this->success('');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    /**
     * 删除配送员
     */
    public function del()
    {
        if (IS_POST) {
            $status = $_POST['status']?:4;
            //$condition_user['group'] = 1;
            $condition_user['uid'] = intval($_POST['uid']);
            if ($this->system_session['area_id']) {
                $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
                $condition_user[$area_index] = $this->system_session['area_id'];
            }
            if ($this->deliver_user->where($condition_user)->setField('status',$status)) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    /**
     * 根据管理员权限获取配送区域
     */
    public function getDeliveryCustom($filterCity = 0)
    {
        $where = [];
        $areaId = $this->system_session['area_id'];
        $cityIds = D('Area')->getCityBuyAreaId($areaId);
        if($cityIds){
            $where['area_id'] = ['IN',$cityIds];
        }
        //是否存在默认筛选城市
        $defaultCity = [];
        if($filterCity > 0){
            $defaultCity = D('Area')->getCityBuyAreaId($filterCity);
        }

        $rs = M('Deliver_custom')->where($where)->select();
        foreach ($rs as &$v) {
            $v['show'] = in_array($v['area_id'], $defaultCity) ? 1 : 0;
            $js = polygon_2_type($v['delivery_range_polygon'], 'array');
            $v['delivery_range_polygon_for_map'] = '';
            foreach ($js as $j) {
                $v['delivery_range_polygon_for_map'] .= '|' . $j['lat'] . '-' . $j['lng'];
            }
            $v['delivery_range_polygon_for_map'] = trim($v['delivery_range_polygon_for_map'], '|');
        }
        return $rs;
    }
    
    /**
     * 配送员添加
     */
    public function user_add($postData = []) {
    	if($_POST || $postData){
    	    if($postData){
    	        $_POST = $postData;
            }
            if (!empty($_POST['card_number'])) {
                $column['card_number'] = $_POST['card_number'];
            }
            if (isset($_POST['register_type'])) {
                $column['register_type'] = $_POST['register_type'];
            }
            if (isset($_POST['user_type'])) {
                $column['user_type'] = $_POST['user_type'];
            }
            $column['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
    		$column['phone'] = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    		$column['pwd'] = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
    		$column['store_id'] = 0;
    		$column['city_id'] = $_POST['city_id'];
    		$column['province_id'] = $_POST['province_id'];
    		$column['circle_id'] = $_POST['circle_id'];
    		$column['area_id'] = $_POST['area_id'];
            if(!empty($_POST['street_id'])){
                $column['street_id'] = $_POST['street_id'];
            }
    		$column['site'] = $_POST['adress'];
    		$long_lat = explode(',',$_POST['long_lat']);
    		$column['lng'] = $long_lat[0];
    		$column['lat'] = $long_lat[1];
            if (C('config.tianditu_web_key')) {
                list($column['lng'], $column['lat']) = Coordinate::wgs84ToBd09($column['lng'], $column['lat']);
            }
    		$column['create_time'] = $_SERVER['REQUEST_TIME'];
    		$column['status'] = intval($_POST['status']);
    		$column['last_time'] = $_SERVER['REQUEST_TIME'];
    		$column['group'] = 1;
    		$column['range'] = intval($_POST['range']);
    		$column['sort'] = intval($_POST['sort']);
    		$column['max_num'] = intval($_POST['max_num']);
            $column['is_system'] = intval($_POST['is_system']);
            $column['traffic'] = $_POST['traffic'];
            $column['speed_per_hour'] = $_POST['speed_per_hour'];
            $column['phone_country_type'] = intval($_POST['phone_country_type']);
            $column['phone'] = phone_format($column['phone_country_type'], $column['phone']);
            $column['face_recognition_images'] = $_POST['face_recognition_images'] ?? '';
            if($_POST['take_percent'] > 100){
                $this->error('抽成比例超出配送员辛苦费了！！');
            }
            $column['take_percent'] = $_POST['take_percent'];//配送员抽成
    		$column['is_cancel_order'] = intval($_POST['is_cancel_order']);
    		$column['delivery_range_type'] = intval($_POST['delivery_range_type']);
            $column['order_type'] = $_POST['order_type'];
    		if (empty($column['name'])) {
    			$this->error('姓名不能为空');
    		}
    		if (empty($column['phone'])) {
    			$this->error('联系电话不能为空');
    		}
            if (!preg_match('/^\d+$/', $column['phone'])) {
                $this->error(L_('联系电话格式不正确'));
            }
			if (empty($column['area_id'])) {
    			$this->error('请选择区域');
    		}
			/*if (empty($column['circle_id'])) {
    			$this->error('请选择商圈');
    		}*/
    		if ($column['delivery_range_type'] == 1) {
                $deliverCustomRc = M('Deliver_custom')->where(['id'=>$_POST['custom_id']])->find();
                if(!$deliverCustomRc){
                    $this->error('配送自定义范围不存在');
                }
                $column['custom_id'] = $_POST['custom_id'];
                $column['delivery_range_polygon'] = $deliverCustomRc['delivery_range_polygon'];
    		    /*if ($_POST['delivery_range_polygon']) {
    		        $latLngArray = explode('|', $_POST['delivery_range_polygon']);
    		        if (count($latLngArray) < 3) {
    		            $this->error('请绘制一个合理的服务范围！');
    		        } else {
    		            $latLngData = array();
    		            foreach ($latLngArray as $row) {
    		                $latLng = explode('-', $row);
    		                $latLngData[] = $latLng[1] . ' ' . $latLng[0];
    		            }
    		            $latLngData[] = $latLngData[0];
    		            $column['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
    		        }
    		    } else {
    		        $this->error('请绘制您的服务范围！');
    		    }*/
    		}
    		if (empty($column['pwd'])) {
    			$this->error('密码不能为空');
    		}
    		if ($column['max_num'] < 0) {
    		    $this->error('配送员手中待配送完成的订单数小于多少单后才可继续抢单的值应该填写大于等于0的整数');
    		}
            //校验密码
            $check_password = verifyPassword($column['pwd']);
            if($check_password['code']!=0){
                $this->error($check_password['msg']);
            }
    		$column['pwd'] = md5($column['pwd']);
    		if ($this->deliver_user->field(true)->where(array('phone' => $column['phone'], 'status' => array('neq',4)))->find()) {
    			$this->error('该手机号已经是配送员账号了，不能重复申请');
    		}
    		
    		$id = $this->deliver_user->data($column)->add();
    		if(!$id){
    			$this->error('保存失败，请重试');
    		}
            if ($postData) {
                return true;
            } else {
                $this->success('保存成功');
            }
    	}
    	$deliverCustoms = $this->getDeliveryCustom($this->config['now_city']);
    	$this->assign('deliverCustoms',$deliverCustoms);
		
		$default_city = M('Area')->where(array('area_id'=>$this->config['now_city']))->find();
		$this->assign('default_city', $default_city);
    	
        $this->assign('traffic', $this->deliver_user->traffic);
    	$this->display();
    }
    
    /**
     * 配送员修改
     */
    public function user_edit() {
    	if($_POST){
    		$uid = intval($_POST['uid']);
            $now_user = $this->deliver_user->where(array('uid'=>$uid))->find();
            if(!$now_user){
                $this->error(L_('配送员不存在！'));
            }
            if($now_user['group'] == 2){
                $this->error(L_('无法修改商家配送员信息！'));
            }
            
    		$column['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
    		$column['phone'] = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    		$column['pwd'] = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
    		if($column['pwd']){
                //校验密码
                $check_password = verifyPassword($column['pwd']);
                if($check_password['code']!=0){
                    $this->error($check_password['msg']);
                }
    			$column['pwd'] = md5($column['pwd']);
    		} else {
    			unset($column['pwd']);
    		}
    		$column['city_id'] = $_POST['city_id'];
            $column['show_phone_status'] = $_POST['show_phone_status'];
    		$column['province_id'] = $_POST['province_id'];
    		$column['circle_id'] = $_POST['circle_id'];
    		$column['area_id'] = $_POST['area_id'];
            $column['street_id'] = $_POST['street_id'];
    		$column['site'] = $_POST['adress'];
    		$long_lat = explode(',',$_POST['long_lat']);
    		$column['lng'] = $long_lat[0];
    		$column['lat'] = $long_lat[1];
            //配置了天地图key需要转换经纬度
            if (C('config.tianditu_web_key')) {
                list($column['lng'], $column['lat']) = Coordinate::wgs84ToBd09($column['lng'], $column['lat']);
            }
    		$column['status'] = intval($_POST['status']);
    		$column['last_time'] = $_SERVER['REQUEST_TIME'];
    		$column['range'] = intval($_POST['range']);
    		$column['sort'] = intval($_POST['sort']);
    		$column['max_num'] = intval($_POST['max_num']);
            if($_POST['take_percent'] > 100){
                $this->error('抽成比例超出配送员辛苦费了！！');
            }
            $column['take_percent'] = $_POST['take_percent'];//配送员抽成
    		$column['is_cancel_order'] = intval($_POST['is_cancel_order']);
    		$column['delivery_range_type'] = intval($_POST['delivery_range_type']);
    		$column['phone_country_type'] = intval($_POST['phone_country_type']);
            $column['order_type'] = $_POST['order_type'];
    		if(isset($_POST['user_type'])){
                $column['user_type'] = intval($_POST['user_type']);
            }
            $column['traffic'] = $_POST['traffic'];
            $column['speed_per_hour'] = $_POST['speed_per_hour'];

			$column['phone'] = phone_format($column['phone_country_type'], $column['phone']);
			
    		if (empty($column['name'])) {
    			$this->error('姓名不能为空');
    		}
    		if($this->config['is_demo_domain']){
				unset($column['phone']);
			}else if (empty($column['phone'])) {
    			$this->error('联系电话不能为空');
    		}

            if (isset($column['phone']) && !preg_match('/^\d+$/', $column['phone'])) {
                $this->error(L_('联系电话格式不正确'));
            }
            if ($column['delivery_range_type'] == 1) {
                $deliverCustomRc = M('Deliver_custom')->where(['id'=>$_POST['custom_id']])->find();
                if(!$deliverCustomRc){
                    $this->error('配送自定义范围不存在');
                }
                $column['custom_id'] = $_POST['custom_id'];
                $column['delivery_range_polygon'] = $deliverCustomRc['delivery_range_polygon'];
                /*if ($_POST['delivery_range_polygon']) {
                    $latLngArray = explode('|', $_POST['delivery_range_polygon']);
                    if (count($latLngArray) < 3) {
                        $this->error('请绘制一个合理的服务范围！');
                    } else {
                        $latLngData = array();
                        foreach ($latLngArray as $row) {
                            $latLng = explode('-', $row);
							if(count($latLng) == 3){
								if(empty($latLng[0])){
									$latLng[0] = '-' . $latLng[1];
									$latLng[1] = $latLng[2];
								}
							}
                            $latLngData[] = $latLng[1] . ' ' . $latLng[0];
                        }
                        $latLngData[] = $latLngData[0];
                        $column['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
                    }
                } else {
                    $this->error('请绘制您的服务范围！');
                }*/
            }
    		if ($column['max_num'] < 0) {
    		    $this->error('配送员手中待配送完成的订单数小于多少单后才可继续抢单的值应该填写大于等于0的整数');
    		}
    		$user = $this->deliver_user->field(true)->where(array('phone' => $column['phone'], 'status' => array('neq',4)))->find();
    		if ($user && $user['uid'] != $uid) {
    			$this->error('该手机号已经是配送员账号了，不能重复申请');
    		}

            if (C('config.open_deliver_level')) {
                $column['score'] = floatval($_POST['score']);
            }
    		
    		if($this->deliver_user->where(array('uid'=>$uid))->data($column)->save()){
                 if (C('config.open_deliver_level')) {
                    // 配送员积分变更
                    if ($column['score'] != $user['score']) {
                        $score = abs($column['score'] - $user['score']);
                        if ($column['score']>$user['score']) {
                            // 增加积分
                            $desc = '管理员操作增加'.$score.'积分';
                            $res = D('Deliver_score_log')->add_row($uid,1,1,$score,$desc);
                        }else{
                            // 减少积分
                            $desc = '管理员操作减少'.$score.'积分';
                            $res = D('Deliver_score_log')->add_row($uid,2,1,$score,$desc);
                        }
                    }
                }
    			$this->success('修改成功！');
    		}else{
    			$this->error('修改失败！请检查内容是否有过修改（必须修改）后重试~');
    		}
    	}else{
    		$uid = $_GET['uid'];
    		if(!$uid){
    			$this->error('非法操作');
    		}
    		$deliver = $this->deliver_user->where(array('uid'=>$uid))->find();
            //配置了天地图key需要转换经纬度
            if (C('config.tianditu_web_key')) {
                list($deliver['lng'], $deliver['lat']) = Coordinate::bd09ToWgs84($deliver['lng'], $deliver['lat']);
            }
    		
    		if ($deliver['delivery_range_polygon']) {
    		    $deliver['delivery_range_polygon'] = substr($deliver['delivery_range_polygon'], 9, strlen($deliver['delivery_range_polygon']) - 11);
    		    $lngLatData = explode(',', $deliver['delivery_range_polygon']);
                array_pop($lngLatData);
                $lngLats = array();
                foreach ($lngLatData as $lnglat) {
                    $lng_lat = explode(' ', $lnglat);
                    $lngLats[] = $lng_lat[1] . '-' . $lng_lat[0];
                }
                $deliver['delivery_range_polygon'] = implode('|', $lngLats);
            }
    		if(!$deliver){
    			$this->error('非法操作');
    		}
            $deliverCustoms = $this->getDeliveryCustom($deliver['area_id']);
            $this->assign('deliverCustoms',$deliverCustoms);
            // 当前等级
            if (C('config.open_deliver_level')) {
                $now_level = D('Deliver_user')->get_user_level($deliver['score']);
                $deliver['level_name'] = $now_level['name'];
            }
            $this->assign('traffic', $this->deliver_user->traffic);
    		$this->assign('now_user',$deliver);
    	}
    	$this->display();
    }
    
    /**
     * 配送员注册信息
     */
    public function user_registry() {
        if($_POST){
            // 
        }else{
            $uid = $_GET['uid'];
    		if(!$uid){
    			$this->error('非法操作');
    		}
    		$deliver = $this->deliver_user->where(array('uid'=>$uid))->find();
            if($deliver['user_field']) $deliver['user_field'] = unserialize($deliver['user_field']);
            // print_r($deliver);
    		
    		if ($deliver['delivery_range_polygon']) {
    		    $deliver['delivery_range_polygon'] = substr($deliver['delivery_range_polygon'], 9, strlen($deliver['delivery_range_polygon']) - 11);
    		    $lngLatData = explode(',', $deliver['delivery_range_polygon']);
                array_pop($lngLatData);
                $lngLats = array();
                foreach ($lngLatData as $lnglat) {
                    $lng_lat = explode(' ', $lnglat);
                    $lngLats[] = $lng_lat[1] . '-' . $lng_lat[0];
                }
                $deliver['delivery_range_polygon'] = implode('|', $lngLats);
            }
    		if(!$deliver){
    			$this->error('非法操作');
    		}
            $deliverCustoms = $this->getDeliveryCustom($deliver['area_id']);
            $this->assign('deliverCustoms',$deliverCustoms);
            // 当前等级
            if (C('config.open_deliver_level')) {
                $now_level = D('Deliver_user')->get_user_level($deliver['score']);
                $deliver['level_name'] = $now_level['name'];
            }
            $this->assign('traffic', $this->deliver_user->traffic);
    		$this->assign('now_user',$deliver);
    	}
    	$this->display();
    }


	/*配送员等级管理20190318------------ start---------------*/
    public function level_list() {
        $list = M('Deliver_level')->select();
        $this->assign('list', $list);
        $this->display();
    }
    public function level_add() {
        if (IS_POST) {
            $image = D('Image')->handle($this->system_session['id'], 'deliver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            } else {
                $this->error($image['message']);
            }
            
            $_POST['score'] = floatval($_POST['score']);
            $_POST['time'] = time();
            $_POST['last_time'] = time();
            $insert = M('Deliver_level')->add($_POST);
            if( $insert){
                $this->frame_submit_tips(1, '添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{

            $this->display();
        }
    }
    public function level_edit() {
       if (IS_POST) {
            $pigcms_id = intval($_POST['pigcms_id']);
            if (!$pigcms_id) {
                $this->error('参数传递有误');
            }
            
            if ($_FILES['image']['name']) {
                $image = D('Image')->handle($this->system_session['id'], 'deliver', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, $image['url']);
                } else {
                    $this->error($image['message']);
                }
            }
            $_POST['score'] = floatval($_POST['score']);
            $_POST['last_time'] = time();
            $update = M('Deliver_level')->where(array('pigcms_id'=>$pigcms_id))->save($_POST);
            if($update){
                $this->frame_submit_tips(1, '编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $now_level = M('Deliver_level')->where(array('pigcms_id'=>$_GET['pigcms_id']))->find();
            $this->assign('now_level',$now_level);
            $this->display();
        }
    }
    public function level_del() {
        $pigcms_id = intval($_POST['pigcms_id']);
        if (!$pigcms_id) {
            $this->error('参数传递有误');
        }
       
        $del = M('Deliver_level')->where(array('pigcms_id'=>$pigcms_id))->delete();
        if( $del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }   

    // 配送员等级积分记录
    public function user_score_log(){
        $uid = intval($_GET['uid']);
        
        // 配送员等级
        $where['uid'] = $uid;
        $score_log = D('Deliver_score_log')->get_limit_list_page($where,20);
        
        $this->assign('list',$score_log);
        $this->display();
    }

    // 配送员等级规则
    public function level_rule_list() {
        $list = M('Deliver_level_rule')->order('sort desc')->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function level_rule_add() {
        if (IS_POST) {
            $_POST['time'] = time();
            $insert = M('Deliver_level_rule')->add($_POST);
            if ( !$_POST['content'] ) {
                $this->error('内容必填');
            }
            if( $insert){
                $this->frame_submit_tips(1, '添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }

    public function level_rule_edit() {
       if (IS_POST) {
            $pigcms_id = intval($_POST['pigcms_id']);
            if (!$pigcms_id) {
                $this->error('参数传递有误');
            }

            if ( !$_POST['content'] ) {
                $this->error('内容必填');
            }

            $update = M('Deliver_level_rule')->where(array('pigcms_id'=>$pigcms_id))->save($_POST);
            if($update !== false){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $now_rule = M('Deliver_level_rule')->where(array('pigcms_id'=>$_GET['pigcms_id']))->find();
            $this->assign('now_rule',$now_rule);
            $this->display();
        }
    }

    public function level_rule_del() {
        $pigcms_id = intval($_POST['pigcms_id']);
        if (!$pigcms_id) {
            $this->error('参数传递有误');
        }
       
        $del = M('Deliver_level_rule')->where(array('pigcms_id'=>$pigcms_id))->delete();
        if( $del){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }


    /*配送员等级管理20190318------------ end-----------------*/



	public function user_login_log(){
		$this->assign('bg_color', '#F3F3F3');
        $database_user_login_log = D('Deliver_user_login_log');
        $condition_user_login_log['uid'] = intval($_GET['uid']);
        $count = $database_user_login_log->where($condition_user_login_log)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $log_list = $database_user_login_log->field(true)->where($condition_user_login_log)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('pagebar', $p->show());
        $this->assign('log_list', $log_list);
        $this->display();
	}
	
	//配送列表
	public function deliver_List()
	{
		$selectStoreId = I("selectStoreId", 0, 'intval');
		$selectUserId = I("selectUserId", 0, 'intval');
		$phone = I("phone", 0);
		$orderNum = I("orderNum", 0);
		
		//获取商家的所有配送员
		$delivers = D("Deliver_user")->field(true)->where(array('mer_id'=>$mer_id))->order('status DESC')->select();
		foreach ($delivers as $key => $val) {
			if ($val['status'] == 0) {
				$delivers[$key]['name'] = $val['name'] . " (已禁用)";
			}
		}
		$db_arr = array(C('DB_PREFIX').'deliver_supply'=>'s',C('DB_PREFIX').'deliver_user'=>'u',C('DB_PREFIX').'merchant_store'=>'m');//,C('DB_PREFIX').'waimai_order'=>'o'
		$fields = "s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, u.group";
		$where = 'm.store_id=s.store_id AND s.uid=u.uid';
		if ($phone) {
			$where .= " AND s.phone=".$phone;
		}
        if ($selectStoreId) {
            $where .= " AND s.store_id=".$selectStoreId;
        }
        if ($selectUserId) {
            $where .= "  AND s.uid=".$selectUserId;
        }
        
        import('@.ORG.system_page');
        $count_order = D()->table($db_arr)->where($where)->count();
        $p = new Page($count_order, 20);
        $supply_info = D()->table($db_arr)->field($fields)->where($where)->order('s.`supply_id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($supply_info as $key => $value) {
            $supply_info[$key]['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
            if ($value['start_time']) {
                $supply_info[$key]['start_time'] = date("Y-m-d H:i:s", $value['start_time']);
            } else {
                $supply_info[$key]['start_time'] = '-';
            }
            if ($value['end_time']) {
                $supply_info[$key]['end_time'] = date("Y-m-d H:i:s", $value['end_time']);
            } else {
                $supply_info[$key]['end_time'] = '-';
            }
            $supply_info[$key]['paid'] = $value['paid'] == 1? "已支付": "未支付";
            $supply_info[$key]['group'] = $value['group'] == 1? $this->config['deliver_name'] . "员": "店铺配送员";
            $supply_info[$key]['pay_type'] = $value['pay_type'] == "offline"? "线下支付": "线上支付";
            //订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            //配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            switch ($value['status']) {
                case 1:
                    $supply_info[$key]['order_status'] = "等待接单";
                    break;
                case 2:
                    $supply_info[$key]['order_status'] = "已接单";
                    break;
                case 3:
                    $supply_info[$key]['order_status'] = "已取货";
                    break;
                case 4:
                    $supply_info[$key]['order_status'] = "开始配送";
                    break;
                case 5:
                    $supply_info[$key]['order_status'] = "已完成";
                    break;
                case 6:
                    $supply_info[$key]['order_status'] = "已评价";
                    break;
                default:
                    $supply_info[$key]['order_status'] = "订单失效";
                    break;
            }
        }
        $pagebar = $p->show();
        $this->assign('selectStoreId', $selectStoreId);
        $this->assign('phone', $phone);
        $this->assign('orderNum', $orderNum);
        $this->assign('selectUserId', $selectUserId);
        $this->assign('stores', $stores);
        $this->assign('delivers', $delivers);
        $this->assign('pagebar', $pagebar);
        $this->assign('supply_info', $supply_info);

        $this->display();
	}

    public function deliverList()
    {
        $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $street_id = I('get.street_id',0,'intval');

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
		if(empty($_GET)){
			$_GET['day'] = 1;
		}


        $selectStoreId = I("selectStoreId", 0, "intval");
        $selectUserId = I("selectUserId", 0, "intval");
        $orderNum = I("orderNum", 0);
        
        $status = I('status', -1, 'intval');
        $deliver_status = I('deliver_status', -1, 'intval');
        $order_from = isset($_GET['order_from']) ? intval($_GET['order_from']) : - 1;
        $day = I('day', 0, 'intval');
        $period = I('period', '', 'htmlspecialchars');
        
        $timetype = I('timetype', 0, 'intval');
        $time = I('time', 0, 'intval');
        $stime = $etime = 0;
        if ($day) {
            if ($day == 1) {
                $stime = strtotime(date('Y-m-d'));
                $etime = time();
            } else {
                $stime = strtotime("-{$day} day");
                $etime = time();
            }
        }
        if ($period) {
            $time_array = explode('-', $period);
            $stime = strtotime($time_array[0]);
            $etime = strtotime($time_array[1]) + 86400;
        }

        $prefix = C('DB_PREFIX');
        $fied = "s.`supply_id`, s.`deliver_status`, s.`uid`, sp.third_type, ".
            "CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM ".$prefix."service_user_publish AS spps WHERE spps.publish_id = s.order_id) WHEN s.`item`= 8 THEN (SELECT real_orderid FROM ".$prefix."village_group_order AS vgo WHERE vgo.order_id = s.order_id) WHEN s.`item`= 4 THEN (SELECT order_no FROM ".$prefix."mall_order AS mo WHERE mo.order_id = s.order_id)  WHEN s.`item`= 5 THEN (SELECT mo.order_no FROM ".$prefix."mall_new_periodic_purchase_order AS periodic_o LEFT JOIN ".$prefix."mall_order AS mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id) ELSE (SELECT real_orderid FROM ".$prefix."shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn, ".
            "CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM ".$prefix."plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') WHEN s.`item`= 8 THEN (SELECT orderid FROM ".$prefix."plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'village_group') WHEN s.`item`= 4 THEN (SELECT pay_orderno FROM ".$prefix."mall_order AS mo WHERE mo.order_id = s.order_id) WHEN s.`item`= 5 THEN (SELECT mo.pay_orderno FROM ".$prefix."mall_new_periodic_purchase_order AS periodic_o LEFT JOIN ".$prefix."mall_order AS mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id) ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS orderid, ".
            "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_type FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') WHEN s.`item`= 8 THEN (SELECT pay_type FROM ".$prefix."village_group_order AS vgo WHERE vgo.order_id = s.order_id) WHEN s.`item`= 4 THEN (SELECT online_pay_type FROM ".$prefix."mall_order AS mo WHERE mo.order_id = s.order_id)  WHEN s.`item`= 5 THEN (SELECT mo.online_pay_type FROM ".$prefix."mall_new_periodic_purchase_order AS periodic_o LEFT JOIN ".$prefix."mall_order AS mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id) ELSE (SELECT pay_type FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_pay_type, ".
            "CASE  WHEN s.`server_type` <> 0 THEN (SELECT is_mobile_pay FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') WHEN s.`item`= 8 THEN (SELECT is_mobile_pay FROM ".$prefix."plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'village_group') WHEN s.`item`= 4 THEN 0 WHEN s.`item`= 5 THEN 0 ELSE (SELECT is_mobile_pay FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_is_mobile_pay, ".
            "CASE  WHEN s.`server_type` <> 0 THEN (SELECT paid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') WHEN s.`item`= 8 THEN s.paid WHEN s.`item`= 4 THEN (SELECT IF(pay_time>0,1,0) FROM ".$prefix."mall_order AS mo WHERE mo.order_id = s.order_id) WHEN s.`item`= 5 THEN (SELECT IF(mo.pay_time>0,1,0) FROM ".$prefix."mall_new_periodic_purchase_order AS periodic_o LEFT JOIN ".$prefix."mall_order AS mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id)  ELSE (SELECT paid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_paid, ".
            "s.order_id, s.order_time, s.create_time, s.order_from, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.end_time,s.pay_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.distance, s.from_lat, s.aim_lat, s.from_lnt, s.aim_lnt, s.get_type, s.server_type, s.is_fetch_order,s.fetch_number,s.from_site";

        $sql = "SELECT ".$fied." FROM " .
            C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
            C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
            C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
            C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN ".
            C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";

//        $sql_count = "SELECT count(1) AS count,sum(s.freight_charge) AS total_freight,sum(s.money) AS total_money,sum(s.tip_price) AS tip_price, case  when s.`server_type` <> 0 then sp.`order_sn` else so.real_orderid end as order_sn FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " . C('DB_PREFIX'). "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN " .  C('DB_PREFIX'). "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0) LEFT JOIN " . C('DB_PREFIX'). "shop_order AS so ON (so.order_id=s.order_id AND s.`server_type` = 0)";
        $sql_count = "SELECT sum(s.freight_charge) AS total_freight,sum(s.money) AS total_money,sum(s.deliver_user_fee) AS total_deliver_user_fee,sum(s.tip_price) AS tip_price,count(1) AS count FROM " .
             C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
             C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
             C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
             C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN ".
             C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";

        $sql .= ' WHERE s.type=0';
        $sql_count .= ' WHERE s.type=0';
        if ($province_id > 0) {
            $sql .= " AND s.province_id=" . $province_id;
            $sql_count .= " AND s.province_id=" . $province_id;
        }
        if ($city_id > 0) {
            $sql .= " AND s.city_id=" . $city_id;
            $sql_count .= " AND s.city_id=" . $city_id;
        }
        if ($area_id > 0) {
            $sql .= " AND s.area_id=" . $area_id;
            $sql_count .= " AND s.area_id=" . $area_id;
        }
        if ($street_id > 0) {
            $sql .= " AND s.street_id=" . $street_id;
            $sql_count .= " AND s.street_id=" . $street_id;
        }
        // 判断需要查询的类型，做判断
        if (!empty($_GET['keyword'])) {
            $keyword = htmlspecialchars($_GET['keyword']);
            if ($_GET['searchtype'] == 'real_orderid') {
                $order_sql = '(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) WHEN s.`item`= 4 THEN (SELECT order_no FROM '.$prefix.'mall_order AS mo WHERE mo.order_id = s.order_id) WHEN s.`item`= 5 THEN (SELECT mo.order_no FROM '.$prefix.'mall_new_periodic_purchase_order AS periodic_o LEFT JOIN '.$prefix.'mall_order mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn)';
                $sql .= ' AND '.$order_sql.' = "' . $keyword . '"';
                $sql_count .= ' AND '.$order_sql.' = "' . $keyword . '"';
            } if ($_GET['searchtype'] == 'orderid') {
                $orderid_sql = "(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') WHEN s.`item`= 4 THEN (SELECT pay_orderno FROM '.$prefix.'mall_order AS mo WHERE mo.order_id = s.order_id)  WHEN s.`item`= 5 THEN (SELECT mo.pay_orderno FROM '.$prefix.'mall_new_periodic_purchase_order AS periodic_o LEFT JOIN '.$prefix.'mall_order mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id)  ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS orderid)";
                $sql .= ' AND '.$orderid_sql.' = "' . $keyword . '"';
                $sql_count .= ' AND '.$orderid_sql.' = "' . $keyword . '"';
            } elseif ($_GET['searchtype'] == 's_name') {
                $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                $sql_count .= ' AND m.name LIKE "%' . $keyword . '%"';
            } elseif ($_GET['searchtype'] == 'name') {
                $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                $sql_count .= ' AND s.name LIKE "%' . $keyword . '%"';
            } elseif ($_GET['searchtype'] == 'phone') {
                $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                $sql_count .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
            } elseif ($_GET['searchtype'] == 'd_name') {
                $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                $sql_count .= ' AND u.name LIKE "%' . $keyword . '%"';
            }
        }
        if ($time > 0) {
            $sqltime = $time * 60;
            if ($timetype == 0) {
                $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                $sql_count .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
            } elseif ($timetype == 1) {
                $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                $sql_count .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
            } elseif ($timetype == 2) {
                $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                $sql_count .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
            } elseif ($timetype == 3) {
                $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                $sql_count .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
            } elseif($timetype == 4){
                $sql .= " AND s.end_time > 0 AND s.end_time-s.appoint_time>" . $sqltime;
                $sql_count .= " AND s.end_time > 0 AND s.end_time-s.appoint_time>" . $sqltime;
            }
        }
        if ($stime && $etime) {
            $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
            $sql_count .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
        }
        if ($deliver_status > -1) {
            if ($deliver_status == 1) {
                $sql .= " AND ((s.deliver_status=2 AND s.is_fetch_order=0) OR (s.deliver_status=" . $deliver_status . "))";
                $sql_count .= " AND ((s.deliver_status=2 AND s.is_fetch_order=0) OR (s.deliver_status=" . $deliver_status . "))";
            }elseif ($deliver_status == 2) {
                $sql .= " AND s.deliver_status=2 AND s.is_fetch_order=1";
                $sql_count .= " AND s.deliver_status=2 AND s.is_fetch_order=1";
            }elseif($deliver_status == 5) {
                $sql .= " AND (s.deliver_status=5 AND s.status<>0)";
                $sql_count .= " AND (s.deliver_status=5 AND s.status<>0)";
            }elseif($deliver_status == 0) {
                $sql .= " AND (s.deliver_status=0 OR s.status=0)";
                $sql_count .= " AND (s.deliver_status=0 OR s.status=0)";
            } else {
                $sql .= " AND s.deliver_status=" . $deliver_status;
                $sql_count .= " AND s.deliver_status=" . $deliver_status;
            }
        }

        if ($status == 1) {
            $sql .= " AND s.is_fetch_order=0";
            $sql_count .= " AND s.is_fetch_order=0";
        }else if ($status == 2) {
            $sql .= " AND s.is_fetch_order=1";
            $sql_count .= " AND s.is_fetch_order=1";
        }else if ($status != -1) {
            $sql .= " AND s.status=" . $status;
            $sql_count .= " AND s.status=" . $status;
        }

        if ($order_from > - 1) {
            if ($order_from == 0) {
                $sql .= " AND s.order_from<3";
                $sql_count .= " AND s.order_from<3";
            } elseif ($order_from == 4) {
                $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                $sql_count .= " AND (s.order_from=4 OR s.order_from=5)";
            } else {
                $sql .= " AND s.order_from=" . $order_from;
                $sql_count .= " AND s.order_from=" . $order_from;
            }
        }
        
        if ($selectStoreId) {
            $sql .= " AND s.store_id=" . $selectStoreId;
            $sql_count .= " AND s.store_id=" . $selectStoreId;
        }
        
        if ($selectUserId) {
            $sql .= " AND s.uid=" . $selectUserId;
            $sql_count .= " AND s.uid=" . $selectUserId;
        }
        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            $sql .= ' AND ((s.' . $area_index . '>0 AND s.' . $area_index . '=' . $this->system_session['area_id'] . ') OR m.' . $area_index . '=' . $this->system_session['area_id'] . ')';
            $sql_count .= ' AND ((s.' . $area_index . '>0 AND s.' . $area_index . '=' . $this->system_session['area_id'] . ') OR m.' . $area_index . '=' . $this->system_session['area_id'] . ')';
        }
        $platform_sql = $sql_count. " AND s.status > 1 AND s.uid <> 0";//把所有已分配配送员的单子取出来求和
        
        import('@.ORG.system_page');
        $res_count = D()->query($sql_count);
        $platform_count = D()->query($platform_sql);
        $res_count[0]['total_platform_fee'] = $platform_count[0]['total_freight'] - $platform_count[0]['total_deliver_user_fee'];
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);
        foreach ($supply_info as &$value) {
			if($value['status'] != 5 && $value['status'] != 6){
				$value['end_time'] = 0;
			}
			if (3==$value['item']) {
                $value['confirm_time'] = '-';
            } else {
                $value['confirm_time'] = ($value['create_time'] && $value['order_time']) ? intval(($value['create_time'] - $value['order_time']) / 60) . '分钟' : '-';
            }
            $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . '分钟' : '-';
            $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . '分钟' : '-';
            $value['pay_deliver_use_time'] = $value['end_time'] && $value['pay_time'] ? intval(($value['end_time'] - $value['pay_time']) / 60) . '分钟' : '-';
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
            $value['start_time'] = $value['start_time'] ? date("Y-m-d H:i:s", $value['start_time']) : '-';
            $value['end_time'] = $value['end_time'] ? date("Y-m-d H:i:s", $value['end_time']) : '-';
            if ($value['order_from'] == 8) {
                $value['order_paid'] = 1;
            }
            $value['paytypestr'] = D('Pay')->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);

            $value['paid'] = $value['order_paid'] == 1 ? "已支付" : "未支付";

			$value['distance'] = $value['distance'] ? $value['distance'] . 'km' : getRange(getDistance($value['from_lat'], $value['from_lnt'], $value['aim_lat'], $value['aim_lnt']));
            // 订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            // 配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            /*switch ($value['status']) {
                case 1:
                    $value['order_status'] = '<font color="red">等待接单</font>';
                    break;
                case 2:
					if($value['is_fetch_order']){
						$value['order_status'] = "已接单";
					}else{
						$value['order_status'] = '<font color="red">等待接单</font>';
					}
                    break;
                case 3:
                    $value['order_status'] = "已取货";
                    break;
                case 4:
                    $value['order_status'] = "开始配送";
                    break;
                case 5:
                    $value['order_status'] = "已完成";
                    break;
                case 6:
                    $value['order_status'] = "已评价";
                    break;
                case 7:
                    $value['order_status'] = "退款失败";
                    break;
                default:
                    $value['order_status'] = "订单失效";
                    break;
            }*/
            //展示配送员3.0的配送状态
            $value['order_status']  = (new Deliver_supplyModel())->getDeliverStatusDesc($value['deliver_status'],$value['is_fetch_order']);
            if ($value['order_from'] == 10) {
                $uids = D('Village_group_order')->where(['order_id'=>$value['order_id']])->field('uid,address')->find();
                $nick = D('User')->where(['uid'=>$uids['uid']])->field('nickname,phone')->find();
                $value['username'] = $nick['nickname'];
                $value['userphone'] = $nick['phone'];
                if(!$value['aim_site']){
                    $value['aim_site'] = $uids['address'];
                }
            }
			if($value['third_type'] == 'maiyatian') {
				$value['order_source'] = D('Maiyatian_delivery')->getOrderSource($value['order_id']);
			}
        }
        
        $pagebar = $p->show();
        $this->assign(array(
            'status' => $status,
            'deliver_status'=>$deliver_status,
            'order_from' => $order_from,
            'day' => $day,
            'period' => $period,
            'time' => $time,
            'timetype' => $timetype,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'area_id' => $area_id,
            'keyword' => $_GET['keyword'],
            'searchtype' => $_GET['searchtype']
        ));
        $this->assign('selectStoreId', $selectStoreId);
        $this->assign('orderNum', $orderNum);
        $this->assign('selectUserId', $selectUserId);
        $this->assign('pagebar', $pagebar);
        $this->assign('supply_info', $supply_info);
        $this->assign('res_count', $res_count);
        $this->assign('platform_count',$platform_count);
        $this->display();
    }
    
    // 配送列表 操作
    public function appoint_deliver()
    {
        $supply_id = isset($_REQUEST['supply_id']) ? intval($_REQUEST['supply_id']) : 0;
    	$supply = D('Deliver_supply')->field(true)->where(array('supply_id' => $supply_id, 'type' => 0))->find();
    	if (empty($supply)) $this->frame_error_tips('不存在的数据');

        $checkOrderType = 0;
        if (in_array($supply['item'], [2, 4, 5])) {
            $checkOrderType = 1;
        } else if ($supply['item'] == 3 && in_array($supply['server_type'], [2, 3])) {
            $checkOrderType = 2;
        } else if ($supply['item'] == 3 && $supply['server_type'] == 1) {
            $checkOrderType = 3;
        }else if ($supply['item'] ==8) {
            $checkOrderType = 4;
        }

    	if (IS_POST) {
    		if ($supply['status'] > 4) $this->error('配送已完成，不能重新指派了');
    		$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
			
			if($uid == $supply['uid']) $this->error('该订单已经属于该配送员');
			
    		$user = D('Deliver_user')->field(true)->where(array('uid' => $uid, 'group' => 1, 'status' => 1))->find();
    		if (empty($user)) $this->error('配送员不存在');
    		$status = $supply['status'] == 1 ? 2 : $supply['status'];
    		$save_data = array('uid' => $uid, 'status' => $status);
			if ($status == 2) {
				$save_data['start_time'] = time();
			}
			$from_type = 1;//0:默认用户操作，1：系统指派，2：系统更换，3：计划任务指定
    		if ($supply['uid']) {
    		    $from_type = 2;
    			$save_data['get_type'] = 2;
    			$save_data['change_log'] = $supply['change_log'] ? $supply['change_log'] . ',' . $supply['uid'] : $supply['uid'];

    		} else {
    			$save_data['get_type'] = 1;
                if ($supply['status'] == 1) {
                    $offer_id = D('Service_offer')->add_offer($supply['order_id'], $uid);
                    $save_data['offer_id'] = $offer_id;
                }
            }
            $save_data['deliver_check'] = 0;
            $save_data['is_fetch_order'] = 0;
            $save_data['check_time'] = time()+$this->config['deliver_look_out_time']*60;
            if($supply['item'] == '3'){//帮买帮送
                $publishInfo = D('Service_user_publish')->where(array('publish_id'=> $supply['order_id']))->find();
                if($publishInfo['catgory_type'] == 2){
                    //帮我买
                    $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }
                if($publishInfo['catgory_type'] == 3){
                    //帮我送
                    $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }
                if($cat_field_info){
                    $fc = $cat_field_info['basic_distance_price'] + $cat_field_info['weight_price'] + $cat_field_info['distance_price'];//基础配送费+超重费用+超距离配送
                    $save_data['deliver_user_fee'] = $fc - ($fc * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
                }

                if (C('config.open_paotui_price_template')) {
                    $calDeliverUserFeeRs = invoke_v20_service('\app\paotui\model\service\ServiceUserPublishService/calDeliverUserFee', [$uid, array_merge($publishInfo, $cat_field_info)]);
                    $calDeliverUserFeeRs = $calDeliverUserFeeRs['data']['retval'] ?? [];
                    if (empty($calDeliverUserFeeRs)) {
                        $this->error('配送员所得费用计算失败,请重试');
                    }
                    $save_data['deliver_user_fee'] = $calDeliverUserFeeRs['total_fee'];
                }
            }
            else{
                $save_data['deliver_user_fee'] = $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
            }
            if(!empty($user)){
                $save_data['uid']=$user['uid'];
            }
            $result = D('Deliver_supply')->where(array('supply_id' => $supply_id))->save($save_data);
            fdump(D('Deliver_supply')->getLastSql(),"ALLOrderdeliverUidLog",1);
            if($supply['item'] == '2'){//快店订单出现配送员信息为空的情况
                $msg=D('Deliver_supply')->where(array('supply_id' => $supply_id))->find();
                if(empty($msg['uid']) && $user['uid']>0){
                    $save_data1['uid']=$user['uid'];
                    D('Deliver_supply')->where(array('supply_id' => $supply_id))->save($save_data1);
                }
                fdump(D('Deliver_supply')->getLastSql(),"deliverUidShopOrderLog",1);

                $deliverInfo = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
                D("Shop_order")->where(array('order_id' => $supply['order_id']))->data(['deliver_info' => $deliverInfo])->save();
            }
  

    		if ($status == 2) {
    			if ($supply['item'] == 0) {
    				$result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 8))->save();
                    if($supply['order_from']==11){//积分商城订单
                        D("System_order")->save_order_status('gift', $supply['order_id'], 1);
                    }
    			} elseif ($supply['item'] == 2) {
    				$deliver_info = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
    				$result = D("Shop_order")->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 2, 'deliver_info' => $deliver_info))->save();
                    // 订单详情
                    $order =  D("Shop_order")->where(array('order_id' => $supply['order_id']))->find();
                    // 周期购
                    if($order['periodic_type']){//周期购
                        $p_data = array();
                        $p_data['status'] = 1;
                        $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['deliver_uid'] = $user['uid'];
                        $p_data['deliver_type'] = 2;
                        $p_data['deliver_money'] = $supply['freight_charge'];

                        $p_where = array();
                        //获得最近需要配送的周期
                        $periodic_deliver = M('Mall_periodic_deliver')->where(array('order_id'=>$supply['order_id'],'status'=>0))->order('deliver_num asc')->find();
                        $p_where['id'] = $periodic_deliver['id'];
                        M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save();
                    }
                    D('Shop_order_log')->add_log(array('order_id' => $supply['order_id'], 'from_type' => $from_type, 'status' => 34, 'name' => $user['name'], 'phone' => $user['phone'],'periodic_id'=>intval($periodic_deliver['id'])));
    			} elseif ($supply['item'] == 3) {
    			    $offer_id = D('Service_offer')->add_offer($supply['order_id'], $user['uid'], $this->system_session['id']);
    			    $data = array('offer_id' => $offer_id);
    			    if ($supply['server_type'] != 1 && !$supply['appoint_time']) {
    			        //$data['appoint_time'] = time() + $supply['server_time'] * 60;
    			    }
    			    D('Deliver_supply')->where(array('supply_id' => $supply_id))->save($data);
    			} elseif ($supply['item'] == 8) {
                    // 社区拼团
                    $deliver_info = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
                    D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 1, 'deliver_info' => $deliver_info))->save();

                    $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

                    foreach ($detail_info as $value) {
                        $param['goods_id'] = $value['goods_id'];
                        $param['from_type'] = 1;
                        $param['deliver_status'] = 6;
                        $param['note'] = '系统分配配送员接单';
                        $param['name'] = $this->system_session['account'];

                        $param['order_id'] = $value['order_id'];
                        $param['order_id'] = $value;
                        D('Village_group')->add_log($param);
                    }
                }
    		} else {
    		    D('Shop_order_log')->add_log(array('order_id' => $supply['order_id'], 'from_type' => $from_type, 'status' => $status + 1, 'name' => $user['name'], 'phone' => $user['phone']));
    		}

    		D('Deliver_supply')->sendNotice($user, $supply);
    		$this->success('指派成功');
    	} else {
            if ($supply['item'] != 3 && $supply['item'] != 8 && $supply['order_from'] != 11) {
                $store = D('Merchant_store')->field(true)->where(array('store_id' => $supply['store_id']))->find();
                if (empty($store)) $this->frame_error_tips('店铺不存在');
            }
			$users = D('Deliver_user')->hasUser($supply['from_lat'], $supply['from_lnt']);
			if (empty($users)) $this->frame_error_tips('当前暂无工作中或正常范围内的配送员指派');
			
			$uids = '';
			$pre = '';
			$data = array();
			foreach ($users as $user) {
                //判断配送员是否能接该类型订单
                $orderTypes = explode(',', $user['order_type']);
                if ($checkOrderType > 0 && (empty($orderTypes) || !in_array($checkOrderType, $orderTypes))) {
                    continue;
                }
                if(!$user['lat'] || !$user['lng']){
                    continue;
                }

				$user['range'] = getRange(getDistance($supply['from_lat'], $supply['from_lnt'], $user['lat'], $user['lng']));
				$user['now_range'] = getRange(getDistance($supply['from_lat'], $supply['from_lnt'], $user['lat'], $user['lng']));
				$data[$user['uid']] = $user;
				$uids .= $pre . $user['uid'];
				$pre = ',';
			}
			$sql = "SELECT a.pigcms_id, a.uid, a.lat, a.lng FROM " . C('DB_PREFIX') . "deliver_user_location_log AS a INNER JOIN (SELECT uid, MAX(pigcms_id) AS pigcms_id FROM " . C('DB_PREFIX') . "deliver_user_location_log GROUP BY uid) AS b ON a.uid = b.uid AND a.pigcms_id = b.pigcms_id WHERE a.uid IN ({$uids})";
			$now_users = D()->query($sql);
			foreach ($now_users as $v) {
				if (isset($data[$v['uid']]) && floatval($v['lat']) && floatval($v['lng'])) {
					$data[$v['uid']]['now_range'] = getRange(getDistance($supply['from_lat'], $supply['from_lnt'], $v['lat'], $v['lng']));
				}
			}
			$this->assign('supply', $supply);
			$this->assign('users', $data);
			$this->display();
    	}
    }
    public function count_log()
    {
    	$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    	$condition_user = array('mer_id' => 0, 'uid' => $uid);
        $user = $this->deliver_user->field(true)->where($condition_user)->find();
        if (empty($user)) $this->error('不存在的配送员');
        $deliver_count_obj = D('Deliver_count');
        $count = $deliver_count_obj->field(true)->where(array('uid' => $uid))->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $count_list = $deliver_count_obj->field(true)->where(array('uid' => $uid))->order('`today` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($count_list as &$row) {
        	$row['today'] = date('Y-m-d', strtotime($row['today'] . '000000'));
        }
        $this->assign('count_list', $count_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('user', $user);
        $this->display();
    }
    
    public function log_list() 
    {
    	$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    	$order_from = isset($_GET['order_from']) ? intval($_GET['order_from']) : -1;
		$begin_time = isset($_GET['begin_time']) ? htmlspecialchars($_GET['begin_time']) : '';
		$end_time = isset($_GET['end_time']) ? htmlspecialchars($_GET['end_time']) : '';
    	$condition_user = array('mer_id' => 0, 'uid' => $uid);
        $user = $this->deliver_user->field(true)->where($condition_user)->find();
        if (empty($user)) $this->error('不存在的配送员');
        $sql = "SELECT s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone,s.freight_charge, m.name as storename, s.money, s.start_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.tip_price, s.server_type FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
        $sql_count = "SELECT count(1) AS count,sum(s.freight_charge) AS total_freight,sum(s.distance) as total_distance,sum(s.money) AS total_money,sum(s.tip_price) AS tip_price,sum(s.deliver_user_fee) as total_deliver_user_fee FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
        
        $sql .= ' WHERE s.type=0 AND s.status>0 AND s.uid=' . $uid;
        $sql_count .= ' WHERE s.type=0 AND s.status>0 AND s.uid=' . $uid;
        
		if ($begin_time && $end_time) {
			$sql .= ' AND s.end_time>' . strtotime($begin_time) . ' AND s.end_time<' . strtotime($end_time);
			$sql_count .= ' AND s.end_time>' . strtotime($begin_time) . ' AND s.end_time<' . strtotime($end_time);
		}
        if ($order_from > - 1) {
            if ($order_from == 0) {
                $sql .= " AND s.order_from<3";
                $sql_count .= " AND s.order_from<3";
            } elseif ($order_from == 4) {
                $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                $sql_count .= " AND (s.order_from=4 OR s.order_from=5)";
            } else {
                $sql .= " AND s.order_from=" . $order_from;
                $sql_count .= " AND s.order_from=" . $order_from;
            }
        }
        import('@.ORG.system_page');
        
        $res_count = D()->query($sql_count);
        $res_count[0]['total_platform_fee'] = round(($res_count[0]['total_freight'] - $res_count[0]['total_deliver_user_fee'])*100)/100;
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;

        
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);

        foreach ($supply_info as &$value) {
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time']-$value['start_time'])/60).'分钟' : '-';
			$value['start_time'] = $value['start_time'] ? date("Y-m-d H:i:s", $value['start_time']) : '-';
			$value['end_time'] = $value['end_time'] ? date("Y-m-d H:i:s", $value['end_time']) : '-';
            $value['paid'] = $value['paid'] == 1 ? "已支付" : "未支付";
            $value['pay_type'] = $value['pay_type'] == "offline" ? "线下支付" : "线上支付";
            //订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            //配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            switch ($value['status']) {
                case 1:
                    $value['order_status'] = '<font color="red">等待接单</font>';
                    break;
                case 2:
                    $value['order_status'] = "接单";
                    break;
                case 3:
                    $value['order_status'] = "取货";
                    break;
                case 4:
                    $value['order_status'] = "开始配送";
                    break;
                case 5:
                    $value['order_status'] = "完成";
                    break;
                case 6:
                    $value['order_status'] = "已评价";
                    break;
                default:
                    $value['order_status'] = "订单失效";
                    break;
            }
            if($value['item'] == 8){
                $uids = D('Village_group_order')->where(['order_id'=>$value['order_id']])->field('uid,address')->find();
                $nick = D('User')->where(['uid'=>$uids['uid']])->field('nickname,phone')->find();
                $value['username'] = $nick['nickname'];
                $value['userphone'] = $nick['phone'];
                if(!$value['aim_site']){
                    $value['aim_site'] = $uids['address'];
                }
            }
        }

        $this->assign('supply_info', $supply_info);
        $this->assign('pagebar', $p->show());
        $this->assign('user', $user);
        $this->assign('res_count', $res_count);
        $this->assign(array('begin_time' => $begin_time, 'end_time' => $end_time, 'order_from' => $order_from));
        $this->display();
    }
    
    
    public function change()
    {
    	$supply_id = isset($_GET['supply_id']) ? intval($_GET['supply_id']) : 0;
    	$supply = D('Deliver_supply')->field(true)->where(array('supply_id' => $supply_id, 'type' => 0))->find();
    	if (empty($supply)) exit(json_encode(array('error_code' => true, 'msg' => '不存在的数据')));
    	if ($supply['status'] == 5 || $supply['status'] == 0) exit(json_encode(array('error_code' => false, 'msg' => '修改成功')));
    	if ($supply['status'] == 1) exit(json_encode(array('error_code' => true, 'msg' => '配送员还未接单，不能修改成已完成')));
    	
    	$columns = array();
    	$columns['status'] = 5;
    	$columns['end_time'] = time();
    	
    	$database_deliver_user = D('Deliver_user');
    	$date = 0;
    	if ($now_deliver_user = $database_deliver_user->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find()) {
    		$today = date('Ymd');
    		$num = 0;
    		if ($now_deliver_user['today'] != $today) {
    			$date = $now_deliver_user['today'];
    			$num = $now_deliver_user['today_num'];
    			$deliver_user_data['today'] = $today;
    			$deliver_user_data['today_num'] = 1;
    			$deliver_user_data['num'] = $now_deliver_user['num'] + 1;
    		} else {
    			$deliver_user_data['today_num'] = $now_deliver_user['today_num'] + 1;
    			$deliver_user_data['num'] = $now_deliver_user['num'] + 1;
    		}
    		$database_deliver_user->where(array('uid' => $supply['uid']))->save($deliver_user_data);
    	}
    	
    	if (D('Deliver_supply')->where(array("supply_id" => $supply_id))->save($columns)) {
	  		if ($supply['item'] == 0) {
	    		if ($order = D("Meal_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
	    			$data = array('order_status' => 1, 'status' => 1);//配送状态更改成已完成，订单状态改成已消费
	    			if ($order['paid'] == 0) {
	    				$data['paid'] = 1;
	    				if (empty($data['pay_type']) && empty($data['pay_time'])) $data['pay_type'] = 'offline';
	    			}
	    			if (empty($order['pay_time'])) $data['pay_time'] = time();
	    			if (empty($order['use_time'])) $data['use_time'] = time();
	    			if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];
	    			if ($result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data($data)->save()) {
	    				$this->meal_notice($order);
	    			} else {
	    				exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
	    			}
	    		} else {
	    			exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
	    		}
	    	} elseif ($supply['item'] == 2) {//快店的配送
	    		if ($order = D("Shop_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
	    			$data = array('order_status' => 5, 'status' => 2);
	    			if ($order['is_pick_in_store'] == 0) {//平台配送
	    				if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
	    					$data['paid'] = $order['paid'] == 0 ? 1 : $order['paid'];
	    					$data['pay_type'] = '';
	    					$data['balance_pay'] = $supply['deliver_cash'];
	    				}
	    			} else {
	    				if ($order['paid'] == 0) {
	    					$data['paid'] = 1;
	    					if (empty($order['pay_type']) && empty($order['pay_time'])) $data['pay_type'] = 'offline';
	    				}
	    			}
	    			if (empty($order['pay_time'])) $data['pay_time'] = time();
	    			if (empty($order['use_time'])) $data['use_time'] = time();
	    			if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];
	    			if ($result = D("Shop_order")->where(array('order_id' => $order['order_id']))->data($data)->save()) {
	    				if ($order['is_pick_in_store'] == 0) {//平台配送
	    					if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
	    						D('User_money_list')->add_row($order['uid'], 1, $supply['deliver_cash'], L_('用户充值用于购买X1产品',['X1'=>$this->config['shop_alias_name']]));
	    						D('User_money_list')->add_row($order['uid'], 2, $supply['deliver_cash'], L_('用户购买X1产品',['X1'=>$this->config['shop_alias_name']]));
	    					}
	    				}
	    				D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
	    				D('Shop_order')->shop_notice($order);
	    				D('Shop_order_log')->add_log(array('order_id' => $order['order_id'], 'status' => 6, 'name' => '系统管理员：' . $this->system_session['realname'], 'phone' => $this->system_session['phone']));

                        //检查含有称重商品的订单，是否需要部分退款
                        if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                            D('Shop_order')->checkWeightOrder($order);
                        }
	    			} else {
	    				exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
	    			}
	    		} else {
	    			exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
	    		}
	    	} elseif ($supply['item'] == 3) {
                D('Service_offer')->offer_save_status($supply['order_id'], $supply['uid'], $supply['offer_id'], 4, $this->system_session['id']);
            }
	    	//统计每日配送订单量
	    	
	    	$total = D('Deliver_supply')->where(array('uid' => $supply['uid'], 'status' => array('gt', 4)))->count();
	    	D('Deliver_user')->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->save(array('num' => $total));
	    	$todayNum = D('Deliver_supply')->where(array('uid' => $supply['uid'], 'status' => array('gt', 4), 'end_time' => array(array('gt', strtotime(date('Y-m-d') . ' 00:00:00')), array('lt', time() + 2))))->count();
	    	$date = date('Ymd');
	    	if ($deliver_count = D('Deliver_count')->field(true)->where(array('uid' => $supply['uid'], 'today' => $date))->find()) {
	    	    D('Deliver_count')->where(array('uid' => $supply['uid'], 'today' => $date))->save(array('num' => $todayNum));
	    	} else {
	    	    D('Deliver_count')->add(array('uid' => $supply['uid'], 'today' => $date, 'num' => 1));
	    	}
	    	exit(json_encode(array('error_code' => false, 'msg' => "更新状态成功")));
    	} else {
    		exit(json_encode(array('error_code' => true, 'msg' => "更新状态失败")));
    	}
    }

    public function change_status()
    {
        $supply_id = isset($_GET['supply_id']) ? intval($_GET['supply_id']) : 0;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $supply = D('Deliver_supply')->field(true)->where(array('supply_id' => $supply_id, 'type' => 0))->find();
        if (empty($supply)) exit(json_encode(array('error_code' => true, 'msg' => '不存在的数据')));
        if ($supply['status'] == 5 || $supply['status'] == 0) exit(json_encode(array('error_code' => false, 'msg' => '修改成功')));
        if ($supply['status'] == 1) exit(json_encode(array('error_code' => true, 'msg' => '配送员还未接单，不能修改成已完成')));

        $columns = array();
        $columns['status'] = $status;
        $columns['end_time'] = time();
        //完成订单
        if ($status == 5) {
            $columns['deliver_status'] = $status;
            $columns['finish_time'] = time();
            $database_deliver_user = D('Deliver_user');
            $date = 0;
            if ($now_deliver_user = $database_deliver_user->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find()) {
                $today = date('Ymd');
                $num = 0;
                if ($now_deliver_user['today'] != $today) {
                    $date = $now_deliver_user['today'];
                    $num = $now_deliver_user['today_num'];
                    $deliver_user_data['today'] = $today;
                    $deliver_user_data['today_num'] = 1;
                    $deliver_user_data['num'] = $now_deliver_user['num'] + 1;
                } else {
                    $deliver_user_data['today_num'] = $now_deliver_user['today_num'] + 1;
                    $deliver_user_data['num'] = $now_deliver_user['num'] + 1;
                }
                $database_deliver_user->where(array('uid' => $supply['uid']))->save($deliver_user_data);
            }

            if (D('Deliver_supply')->where(array("supply_id" => $supply_id))->save($columns)) {
                if ($supply['item'] == 0) {
                    if ($order = D("Meal_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                        $data = array('order_status' => 1, 'status' => 1);//配送状态更改成已完成，订单状态改成已消费
                        if ($order['paid'] == 0) {
                            $data['paid'] = 1;
                            if (empty($data['pay_type']) && empty($data['pay_time'])) $data['pay_type'] = 'offline';
                        }
                        if (empty($order['pay_time'])) $data['pay_time'] = time();
                        if (empty($order['use_time'])) $data['use_time'] = time();
                        if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];
                        if ($result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data($data)->save()) {
                            $this->meal_notice($order);
                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                        }
                    } else {
                        exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                    }
                } elseif ($supply['item'] == 2) {//快店的配送
                    if ($order = D("Shop_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                        $data = array('order_status' => 6, 'status' => 2);
                        if ($order['is_pick_in_store'] == 0) {//平台配送
                            if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                                $data['paid'] = $order['paid'] == 0 ? 1 : $order['paid'];
                                $data['pay_type'] = '';
                                $data['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
                                $order['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
                            }
                        } else {
                            if ($order['paid'] == 0) {
                                $data['paid'] = 1;
                                if (empty($order['pay_type']) && empty($order['pay_time'])) $data['pay_type'] = 'offline';
                            }
                        }
                        if (empty($order['pay_time'])) $data['pay_time'] = time();
                        if (empty($order['use_time'])) $data['use_time'] = time();
                        if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];
                        if ($result = D("Shop_order")->where(array('order_id' => $order['order_id']))->data($data)->save()) {
                            if ($order['is_pick_in_store'] == 0) {//平台配送
                                if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                                    D('User_money_list')->add_row($order['uid'], 1, $supply['deliver_cash'], L_('用户充值用于购买X1产品',['X1'=>$this->config['shop_alias_name']]));
                                    D('User_money_list')->add_row($order['uid'], 2, $supply['deliver_cash'], L_('用户购买X1产品',['X1'=>$this->config['shop_alias_name']]));
                                }
                            }
                            D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                            D('Shop_order')->shop_notice($order);
                            D('Shop_order_log')->add_log(array('order_id' => $order['order_id'], 'status' => 6, 'name' => '系统管理员：' . $this->system_session['realname'], 'phone' => $this->system_session['phone']));
                            
                            D('UserNotice')->addNotice([
                                'type'=>0,
                                'business'=>'shop',
                                'order_id'=>$supply['order_id'],
                                'title'=>'商品已经送达',
                                'content'=>'您的外卖商品已经送达，点击查看订单详情',
                            ]);
                            
                            //检查含有称重商品的订单，是否需要部分退款
                            if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                                D('Shop_order')->checkWeightOrder($order);
                            }

                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                        }
                    } else {
                        exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                    }
                } elseif ($supply['item'] == 3) {
                    D('Service_offer')->offer_save_status($supply['order_id'], $supply['uid'], $supply['offer_id'], 4, $this->system_session['id']);
                }elseif ($supply['item'] == 4 || $supply['item'] == 5){
                    $param = [
                        'type' => $supply['item'] == 4 ? 'order' : 'periodic',
                        'status' => 4,
                        'id' => $supply['order_id'],
                        'lng' => $now_deliver_user['now_lng'],
                        'lat' => $now_deliver_user['now_lat'],
                        'rider_info' => [
                            'id' => $supply['uid'],
                            'name' => $now_deliver_user['name'],
                            'phone' => $now_deliver_user['phone'],
                            'note' => '系统管理员：' . $this->system_session['realname'].' 修改配送状态：配送成功',
                        ]
                    ];
                    invoke_v20_service('\app\mall\model\service\MallOrderService/housemanOrderLog', $param);
                }
                //统计每日配送订单量

                $total = D('Deliver_supply')->where(array('uid' => $supply['uid'], 'status' => array('gt', 4)))->count();
                D('Deliver_user')->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->save(array('num' => $total));
                $todayNum = D('Deliver_supply')->where(array('uid' => $supply['uid'], 'status' => array('gt', 4), 'end_time' => array(array('gt', strtotime(date('Y-m-d') . ' 00:00:00')), array('lt', time() + 2))))->count();
                $date = date('Ymd');
                if ($deliver_count = D('Deliver_count')->field(true)->where(array('uid' => $supply['uid'], 'today' => $date))->find()) {
                    D('Deliver_count')->where(array('uid' => $supply['uid'], 'today' => $date))->save(array('num' => $todayNum));
                } else {
                    D('Deliver_count')->add(array('uid' => $supply['uid'], 'today' => $date, 'num' => 1));
                }

                // 完成订单新增配送小费记录
                if($supply['tip_price'] > 0){
                    $tip_price_log = array(
                        'uid' => $supply['uid'],
                        'income' => 1,
                        'money' => $supply['tip_price'],
                        'note' => '小费收入',
                        'type' => 2,
                        'business_type' => 'service',
                        'is_valid' => 1,
                        'business_id' => $supply['order_id'],
                        'create_time' => time()
                    );
                    $is_tip_price = D('Deliver_user_money_log')->field(true)->where(array('uid' => $supply['uid'], 'type' => 2, 'business_type' => 'service', 'business_id' => $supply['order_id']))->find();
                    if (empty($is_tip_price)) {
                        D('Deliver_user_money_log')->add($tip_price_log);
                    }
                }

                exit(json_encode(array('error_code' => false, 'msg' => "更新状态成功")));
            } else {
                exit(json_encode(array('error_code' => true, 'msg' => "更新状态失败")));
            }
        } elseif ($status == 0) {
            //失败订单
            if (D('Deliver_supply')->where(array("supply_id" => $supply_id))->save($columns)) {
                if ($supply['item'] == 0) {
                    if($supply['order_from']==11){//积分商城
                        if($order = D("Gift_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()){
                            D("Gift_order")->refund($order,1);
                        }
                    }else{
                        if ($order = D("Meal_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                            $data = array('order_status' => 0, 'status' => 4);//配送状态更改，订单状态更改

                            if ($result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data($data)->save()) {

                            } else {
                                exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                            }
                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                        }
                    }
                } elseif ($supply['item'] == 2) {//快店的配送
                    if ($order = D("Shop_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                        $data = array('order_status' => 6, 'status' => 5);

                        if ($result = D("Shop_order")->where(array('order_id' => $order['order_id']))->data($data)->save()) {
                            $data_log['dateline'] = time();
                            $data_log['name'] = $this->system_session['realname'];
                            $data_log['status'] = 34;
                            $data_log['order_id'] = $order['order_id'];
                            $data_log['from_type'] = 1;
                            $data_log['note'] = '系统将配送状态修改为配送失败';
                            D("Shop_order_log")->add($data_log);
                            D('Shop_order')->check_refund($order);
                            //失败订单退未兑换卡牌
                            $flipping_event_awards_log = D('Flipping_event_awards_log')->where(['order_id'=>$order['order_id'],'uid'=>$order['uid'],'status'=>['neq',2]])->select();
                            if($flipping_event_awards_log){
                                foreach ($flipping_event_awards_log as $log_item){
                                    if($log_item['awards_type']==1) {
                                        D('User')->user_money($order['uid'],$log_item['money'],'抽奖免单申请退款扣除余额',24,$order['order_id']);
                                    }
                                    $flip_res = D('Flipping_event_awards_log')->where(['id'=>$log_item['id']])->delete();
                                    $stock_inc_res = D('Flipping_event_awards')->where(['id'=>$log_item['awards_id']])->setInc('stocks_num',1);
                                    fdump_api([$order['order_id'],$order['uid'],$flip_res,D('Flipping_event_awards_log')->getLastSql(),$stock_inc_res,D('Flipping_event_awards')->getLastSql(),$log_item],'flipping_event',1);
                                }
                            }
                            (new Deliver_supplyModel())->order_cancel_notice($supply['order_id'], 'shop');
                            // 推送模板消息
                            $now_order = D('Shop_order')->where(array('order_id' => $supply['order_id']))->find();
                            $now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                            if ($now_user['openid']) {
                                $href = C('config.site_url') . '/wap.php?g=Wap&c=Shop&a=status&order_id=' . $supply['order_id'];
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('TM00017', array(
                                    'href' => $href,
                                    'wecha_id' => $now_user['openid'],
                                    'first' => '您的' . C('config.shop_alias_name') . '订单已取消，系统已自动退款！',
                                    'OrderSn' => $now_order['real_orderid'],
                                    'OrderStatus' => '已完成退款',
                                    'remark' => date('Y-m-d H:i:s')
                                ));
                            }
                            $deliver_user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();
                            if ($deliver_user['openid']) {
                                $href = '';
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('TM00017', array(
                                    'href' => $href,
                                    'wecha_id' => $deliver_user['openid'],
                                    'first' => '用户取消' . C('config.shop_alias_name') . '订单！',
                                    'OrderSn' => $now_order['real_orderid'],
                                    'OrderStatus' => '已取消',
                                    'remark' => date('Y-m-d H:i:s')
                                ));
                            }
                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                        }
                    } else {
                        exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                    }
                } elseif ($supply['item'] == 3) {
                    $user_publish_where['publish_id']  = $supply['order_id'];
                    $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,is_voice,voice_status')->find();

                    // 查询支付订单
                    $plat_where = [
                        'business_type' => 'service', 
                        'business_id' => $supply['order_id']
                    ];// 查询条件
                    if($publishInfo['is_voice'] && $publishInfo['voice_status']==20){// 语音发单 已付尾款只退尾款不退基础费用
                        $plat_where['is_final_pay'] = 1;
                    }

                    // 进行原路退款
                    $plat_info = D('Plat_order')->where($plat_where)->find();
                    $refund = array();
//                    $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_info['order_id'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();
                    $payment_money_order = M('Payment_money_order')->order('id DESC')->where(array('order_id'=>$plat_info['order_id'],'orderid'=> $plat_info['orderid'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();

                    if($plat_info['pay_money'] > 0 && ($plat_info['is_refund'] == 0 || $plat_info['is_refund'] == 4) && $payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba')){

                        if($plat_info['pay_money'] == $payment_money_order['payment_money']){
                            //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                            $plat_info['pay_money'] = $payment_money_order['paid_money'];
                        }
                        $plat_info['payment_money'] = $plat_info['pay_money'];
                        if($payment_money_order['pay_method'] == 'wft'){
                            $pay_method = 'wftpay';
                        }
                        if($payment_money_order['pay_method'] == 'paylinx'){
                            $pay_method = 'paylinx';
                        }
                        if($payment_money_order['pay_method'] == 'wepayez'){
                            $pay_method = 'wepayez';
                        }
                        if($payment_money_order['pay_method'] == 'weikebaba'){
                            $pay_method = 'weikebaba';
                        }
                        import('@.ORG.LowFeePay');
                        $lowfeepay = new LowFeePay($pay_method);
                        $plat_info['order_type'] = 'service';
                        $plat_info['orderNo'] = $payment_money_order['orderNo'];
                        if($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                            $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                            if(!empty($mer_juhe)){
                                $lowfeepay->userId =$mer_juhe['userid'];
                                $plat_info['is_own'] =1 ;
                                $plat_info['orderNo'] =  $plat_info['orderNo'].'_1';
                            }

                        }elseif($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                            $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                            if(!empty($mer_juhe)){
                                $lowfeepay->user_id = $mer_juhe['user_id'];
                                $lowfeepay->key = $mer_juhe['key'];
                                $plat_info['is_own'] =1 ;
                            }

                        }
                        $go_refund_param= $lowfeepay->refund($plat_info);
                        $data_plat_order['order_id'] = $plat_info['order_id'];
                        if(!$go_refund_param['error'] && $go_refund_param['type'] == 'ok'){
                            $data_plat_order['is_refund'] = 1;
                            $refund['error'] = 0;
                        }else{
                            $data_plat_order['refund_detail'] = serialize($go_refund_param);
                            $data_plat_order['is_refund'] = 4;
                            $refund['error'] = 1;
                        }
                        $data_plat_order['last_time'] = time();
                        D('Plat_order')->data($data_plat_order)->save();
                    } else {
                        $refund = D('Plat_order')->order_refund($plat_where);
                    }
                    if (!$refund['error']) {
                        D('Service_user_publish')->where(array('publish_id' => $supply['order_id']))->save(array('status' => 6));
                        D('Service_offer')->where(array('publish_id' => $supply['order_id']))->save(array('status' => 6));
                        D('Deliver_supply')->updateStatusToZero($supply['order_id']);
                        $now_offer = D('Service_offer')->where(array('publish_id' => $supply['order_id']))->find();
                        D("Service_offer_record")->add(array('offer_id' => $now_offer['offer_id'], 'publish_id' => $supply['order_id'], 'add_time' => time(), 'remarks' => '退款成功'));
                        // 推送模板消息
                        $now_order = D('Plat_order')->where($plat_where)->find();
                        $now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                        $order_sn = D('Service_user_publish')->where(array('publish_id' => $supply['order_id']))->getField('order_sn');
                        if ($now_user['openid']) {
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $supply['order_id'];
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                            $model->sendTempMsg('TM00017', array(
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您的跑腿订单已取消，系统已自动退款！',
                                'OrderSn' => $order_sn,
                                'OrderStatus' => '已完成退款',
                                'remark' => date('Y-m-d H:i:s')
                            ));
                        }
                        $deliver_user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();
                        if ($deliver_user['openid']) {
                            $href = '';
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $model->sendTempMsg('TM00017', array(
                                'href' => $href,
                                'wecha_id' => $deliver_user['openid'],
                                'first' => '用户取消跑腿订单！',
                                'OrderSn' => $order_sn,
                                'OrderStatus' => '已取消',
                                'remark' => date('Y-m-d H:i:s')
                            ));
                        }
                        /*-----记录系统订单信息 退款成功-即 19/1/24--服务快派-后台取消订单，改为已退款状态-*/
                        $order_id = M('Plat_order')->where($plat_where)->getField('order_id');
                        D("System_order")->save_order_status('service',$order_id, 4);

                        $user_publish_where['publish_id']  = $supply['order_id'];
                        $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,is_third,third_id,notifyurl')->find();
                        // 处理返回信息
                        // 获取配送员信息
                        $user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();

                        // 添加记录
                        $log = array(
                            'publish_id' => $publishInfo['publish_id'],
                            'uid' => $user['uid']?$user['uid']:0,
                            'status' => 6,
                            'name' => $user?$user['name']:'',
                            'phone' => $user?$user['phone']:'',
                            'remark' => '系统退款成功订单关闭',
                            'type' => 2,
                            'admin_id' => $this->system_session['id']
                        );
                        D('Service_user_publish_log')->add_log($log);
                        if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                            // 回调信息
                            $notifyurl = $publishInfo['notifyurl'];
                            $notify_data = array(
                                'third_id' => $publishInfo['third_id'],
                                'publish_id' => $publishInfo['publish_id'],
                                'status' => 6,
                                'name' => $user?$user['name']:'',
                                'phone' => $user?$user['phone']:'',
                                'msg' => '系统退款成功订单关闭',
                                'time' => time(),
                            );
                            D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                        }
	                    invoke_v20_service('\app\maiyatian\service\MaiyatianPushService/saveDeliveryChange', [
		                    'publish_id' => $supply['order_id'],
		                    'params' => [
			                    'status' => 'CANCEL',
		                    ]
	                    ]);
                    } else {
                        // 退款失败改写状态
                        M('Service_user_publish')->where(array('publish_id' => $supply['order_id']))->save(array('status' => 13));
                        M('Deliver_supply')->where(array('item' => 3, 'order_id' => $supply['order_id']))->save(array('status' => 7));
                        /*-----记录系统订单信息 退款失败-即 19/1/24--服务快派-后台取消订单，改为已取消状态-*/
                        $order_id = M('Plat_order')->where($plat_where)->getField('order_id');
                        D("System_order")->save_order_status('service',$order_id, 5);
                        $user_publish_where['publish_id']  = $supply['order_id'];
                        $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,is_third,third_id,notifyurl')->find();
                        // 处理返回信息

                        // 获取配送员信息
                        $user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();

                        $log = array(
                            'publish_id' => $publishInfo['publish_id'],
                            'uid' => $user['uid']?$user['uid']:0,
                            'status' => 12,
                            'name' => $user?$user['name']:'',
                            'phone' => $user?$user['phone']:'',
                            'remark' => '系统退款失败',
                            'type' => 2,
                            'admin_id' => $this->system_session['id']
                        );
                        D('Service_user_publish_log')->add_log($log);
                        if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                            // 回调信息
                            $notifyurl = $publishInfo['notifyurl'];
                            $notify_data = array(
                                'third_id' => $publishInfo['third_id'],
                                'publish_id' => $publishInfo['publish_id'],
                                'status' => 12,
                                'name' => $user?$user['name']:'',
                                'phone' => $user?$user['phone']:'',
                                'msg' => '系统退款失败',
                                'time' => time(),
                            );
                            D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                        }
                    }
                }else if($supply['item'] == 4 || $supply['item'] == 5){
                    if(intval($supply['item']) == 4){
                        $MallOrderModel = M('Mall_order');
                        $mall_order = $MallOrderModel->where(['order_id'=>$supply['order_id']])->find();
                        $param = [
                            'order_id'  =>  $mall_order['order_id'],
                            'status'    =>  53,
                            'extra'      =>  '管理员将配送状态修改为配送失败'
                        ];
                        invoke_v20_service('\app\mall\model\service\MallOrderService/changeOrderStatus', $param);
                    }
                    //新版商城
                    $thisDeliver = D('Deliver_user')->where(['uid'=>$supply['uid']])->find();
                    $param = [
                        'type' => $supply['item'] == 4 ? 'order' : 'periodic',
                        'status' => 5,
                        'id' => $supply['order_id'],
                        'lng' => $thisDeliver['now_lng'],
                        'lat' => $thisDeliver['now_lat'],
                        'rider_info' => [
                            'id' => $supply['uid'],
                            'name' => $thisDeliver['name'],
                            'phone' => $thisDeliver['phone'],
                        ]
                    ];
                    invoke_v20_service('\app\mall\model\service\MallOrderService/housemanOrderLog', $param);
                }
                    exit(json_encode(array('error_code' => false, 'msg' => "更新状态成功")));
                } else {
                    exit(json_encode(array('error_code' => true, 'msg' => "更新状态失败")));
                }
        } elseif ($status == 4) {
                //配送中
                if (D('Deliver_supply')->where(array("supply_id" => $supply_id))->save(array('status'=>$status))) {
                    if ($supply['item'] == 0) {
                        if ($order = D("Meal_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                            $data = array('order_status' => 5);//配送状态更改，订单状态更改

                            if ($result = D("Meal_order")->where(array('order_id' => $supply['order_id']))->data($data)->save()) {

                            } else {
                                exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                            }
                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                        }
                    } elseif ($supply['item'] == 2) {//快店的配送
                        if ($order = D("Shop_order")->field(true)->where(array('order_id' => $supply['order_id']))->find()) {
                            $data = array('order_status' => 3);

                            if ($result = D("Shop_order")->where(array('order_id' => $order['order_id']))->data($data)->save()) {

                            } else {
                                exit(json_encode(array('error_code' => true, 'msg' => "更新订单信息错误")));
                            }
                        } else {
                            exit(json_encode(array('error_code' => true, 'msg' => "订单信息错误")));
                        }
                    } elseif ($supply['item'] == 3) {
                        $data = array('status' => 9);
                        D('Service_offer')->where(array('offer_id' => $supply['order_id']))->data($data)->save();
                        /*-----记录系统订单信息 配送中-即 19/1/24--服务快派-后台 改为配送中，改为已发货状态-*/
                        $order_id = M('Plat_order')->where(array('business_type' => 'service', 'business_id'=> $supply['order_id']))->getField('order_id');
                        D("System_order")->save_order_status('service',$order_id, 1);

                        $user_publish_where['publish_id']  = $supply['order_id'];
                        $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,is_third,third_id,notifyurl')->find();
                        // 处理返回信息
                        // 获取配送员信息
                        $user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();

                        // 添加记录
                        $log = array(
                            'publish_id' => $publishInfo['publish_id'],
                            'uid' => $user['uid']?$user['uid']:0,
                            'status' => 4,
                            'name' => $user?$user['name']:'',
                            'phone' => $user?$user['phone']:'',
                            'remark' => '后台修改，订单完成',
                            'type' => 2,
                            'admin_id' => $this->system_session['id']
                        );
                        D('Service_user_publish_log')->add_log($log);
                        if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                            // 回调信息
                            $notifyurl = $publishInfo['notifyurl'];
                            $notify_data = array(
                                'third_id' => $publishInfo['third_id'],
                                'publish_id' => $publishInfo['publish_id'],
                                'status' => 4,
                                'name' => $user?$user['name']:'',
                                'phone' => $user?$user['phone']:'',
                                'msg' => '后台修改，订单完成',
                                'time' => time(),
                            );
                            D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                        }
	                    invoke_v20_service('\app\maiyatian\service\MaiyatianPushService/saveDeliveryChange', [
		                    'publish_id' => $publishInfo['publish_id'],
		                    'params' => [
			                    'status' => 'DELIVERING',
		                    ]
	                    ]);
                    }else if($supply['item'] == 4 || $supply['item'] == 5){
                        //新版商城
                        $thisDeliver = D('Deliver_user')->where(['uid'=>$supply['uid']])->find();
                        $param = [
                            'type' => $supply['item'] == 4 ? 'order' : 'periodic',
                            'status' => 2,
                            'id' => $supply['order_id'],
                            'lng' => $thisDeliver['now_lng'],
                            'lat' => $thisDeliver['now_lat'],
                            'rider_info' => [
                                'id' => $supply['uid'],
                                'name' => $thisDeliver['name'],
                                'phone' => $thisDeliver['phone'],
                            ]
                        ];
                        invoke_v20_service('\app\mall\model\service\MallOrderService/housemanOrderLog', $param);
                    }
                    exit(json_encode(array('error_code' => false, 'msg' => "更新状态成功")));
                } else {
                    exit(json_encode(array('error_code' => true, 'msg' => "更新状态失败")));
                }

            } else {
                exit(json_encode(array('error_code' => true, 'msg' => "状态输入有误")));
            }
        }

    private function meal_notice($order)
    {
    	if ($store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find()) {
			//增加商家余额
			$order['order_type']='meal';
			$info = unserialize($order['info']);
			$info_str = '';
			foreach($info as $v){
				$info_str.=$v['name'].':'.$v['price'].'*'.$v['num'].'</br>';
			}
			//D('Merchant_money_list')->add_money($this->store['mer_id'],'用户购买'.$info_str.'记入收入',$order);
			$order['desc']='用户购买'.$info_str.'记入收入';
			D('SystemBill')->bill_method($order['is_own'],$order);

            //商家推广分佣
            $now_user = M('User')->where(array('uid' => $order['uid']))->find();
            D('Merchant_spread')->add_spread_list($order, $now_user, 'meal', $now_user['nickname'] . '用户购买餐饮商品获得佣金');

	    	//积分
            if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)){
                
                 $now_merchant = D('Merchant')->get_info($store['mer_id']);
                if($now_merchant['score_get_percent']>=0){
                    $this->config['score_get'] = $now_merchant['score_get_percent']/100;
                }
                $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);

                if ($this->config['score_get']>0 && ($order['payment_money'] + $order['balance_pay'])>0) {
                    D('User')->add_score($order['uid'], (($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']), '在 ' . $store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').' 获得'.$this->config['score_name']);
                    D('Scroll_msg')->add_msg('meal',$now_user['uid'],'用户'.str_replace_name($now_user['nickname']).'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'在'. $store['name'] . ' 中消费获得'.$this->config['score_name']);
                }
            }
			D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], '在 ' . $store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').' 获得积分');
			
			//短信
			$sms_data = array('mer_id' => $store['mer_id'], 'store_id' => $store['store_id'], 'type' => 'food');
			if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
				if (empty($order['phone'])) {
					$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
					$order['phone'] = $user['phone'];
				}
				$sms_data['uid'] = $order['uid'];
				$sms_data['mobile'] = $order['phone'];
				$sms_data['sendto'] = 'user';
				$sms_data['content'] = '您在 ' . $store['name'] . '店中下的订单(订单号：' . $order['order_id'] . '),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！';
				Sms::sendSms($sms_data);
			}
			if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $store['phone'];
				$sms_data['sendto'] = 'merchant';
				$sms_data['content'] = '顾客购买的' . $order['name'] . '的订单(订单号：' . $order['order_id'] . '),已经完成了消费！';
				Sms::sendSms($sms_data);
			}
			
    	}
    }

    
	public function export() 
	{

        $param = $_POST;
        $param['type'] = 'deliver_list';
        $param['rand_number'] = time();
        $param['system_session'] = $this->system_session;
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
        die();


		set_time_limit(0);	
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = '配送列表';
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		// 设置当前的sheet
		$status = I('status', 0, 'intval');
		$day = I('day', 0, 'intval');
		$period = I('period', '', 'htmlspecialchars');
		$order_from = isset($_GET['order_from']) ? intval($_GET['order_from']) : -1;



        $province_id = I('province_id', 0, 'intval');
        $city_id = I('city_id', 0, 'intval');
        $area_id = I('area_id', 0, 'intval');
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
		
		$timetype = I('timetype', 0, 'intval');
		$time = I('time', 0, 'intval');
		$stime = $etime = 0;
		if ($day) {
            if ($day == 1) {
                $stime = strtotime(date('Y-m-d'));
                $etime = time();
            } else {
                $stime = strtotime("-{$day} day");
                $etime = time();
            }
		}
		if ($period) {
			$time_array = explode('-', $period);
			$stime = strtotime($time_array[0]);
			$etime = strtotime($time_array[1]) + 86400;
		}

        //$sql = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";
        $sql = "SELECT count(1) AS cnt FROM " .
            C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
            C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
            C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
            C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN ".
            C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";


        $sql .= ' WHERE s.type=0';
        if ($province_id > 0) {
            $sql .= " AND s.province_id=" . $province_id;
        }
        if ($city_id > 0) {
            $sql .= " AND s.city_id=" . $city_id;
        }
        if ($area_id > 0) {
            $sql .= " AND s.area_id=" . $area_id;
        }
        // 判断需要查询的类型，做判断
        $keyword = I('keyword', '', 'htmlspecialchars');
        $searchtype = I('searchtype', '', 'htmlspecialchars');
        if (!empty($keyword)) {
            if ($searchtype == 'real_orderid') {
                $order_sql = '(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn)';
                $sql .= ' AND '.$order_sql.' = "' . $keyword . '"';
            } elseif ($_GET['searchtype'] == 'orderid') {
                $orderid_sql = "(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id)";
                $sql .= ' AND '.$orderid_sql.' = "' . $keyword . '"';
            } elseif ($searchtype == 's_name') {
                $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
            } elseif ($searchtype == 'name') {
                $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
            } elseif ($searchtype == 'phone') {
                $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
            } elseif ($searchtype == 'd_name') {
                $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
            }
        }


        if ($time > 0) {
            $sqltime = $time * 60;
            if ($timetype == 0) {
                $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
            } elseif ($timetype == 1) {
                $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
            } elseif ($timetype == 2) {
                $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
            } elseif ($timetype == 3) {
                $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
            }
        }
        if ($stime && $etime) {
            $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
        }


		if ($status != -1) {
		    $sql .= " AND s.status='{$status}'";
		}
		
		if ($order_from > - 1) {
		    if ($order_from == 0) {
		        $sql .= " AND s.order_from<3";
		    } elseif ($order_from == 4) {
		        $sql .= " AND (s.order_from=4 OR s.order_from=5)";
		    } else {
		        $sql .= " AND s.order_from=" . $order_from;
		    }
		}
		
		if ($this->system_session['area_id']) {
		    $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
		    $sql .= ' AND m.' . $area_index . '=' . $this->system_session['area_id'];
		}
		
		$count = D()->query($sql);
		$count = isset($count[0]['cnt']) ? intval($count[0]['cnt']) : 0;
		
		$length = ceil($count / 1000);
        $m_shop_order_detail = M('Shop_order_detail');
        $m_shop_order = M('Shop_order');
        $m_service_user_publish_buy = M('Service_user_publish_buy');
        $m_service_user_publish_give = M('Service_user_publish_give');
        $m_service_user_publish = M('Service_user_publish');
        $m_area = M('Area');
        $d_pay = D('Pay');
		for ($i = 0; $i < $length; $i++) { 
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
	
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千配送订单');
			$objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '订单编号');
            $objActSheet->setCellValue('B1', '配送ID');
            $objActSheet->setCellValue('C1', '订单来源');
            $objActSheet->setCellValue('D1', '商品名称');
            $objActSheet->setCellValue('E1', '商品价格');
            $objActSheet->setCellValue('F1', '商品数量');
            $objActSheet->setCellValue('G1', '店铺名称');
            $objActSheet->setCellValue('H1', '店铺商圈');
			$objActSheet->setCellValue('I1', '客户名称');
            $objActSheet->setCellValue('J1', '用户ID');
            $objActSheet->setCellValue('K1', '客户手机');
			$objActSheet->setCellValue('L1', '客户地址');
            $objActSheet->setCellValue('M1', '支付状态');
            $objActSheet->setCellValue('N1', '支付方式');
            $objActSheet->setCellValue('O1', '支付来源');
            $objActSheet->setCellValue('p1', '支付时间');
			$objActSheet->setCellValue('Q1', '订单价格');
			$objActSheet->setCellValue('R1', '应收现金');
			$objActSheet->setCellValue('S1', '配送员昵称');
			$objActSheet->setCellValue('T1', '配送员手机号');
			$objActSheet->setCellValue('U1', '开始时间');
			$objActSheet->setCellValue('V1', '送达时间');
			$objActSheet->setCellValue('W1', '配送费');
			$objActSheet->setCellValue('X1', '小费');
			$objActSheet->setCellValue('Y1', '店员接单时长');
			$objActSheet->setCellValue('Z1', '配送员接单时长');
			$objActSheet->setCellValue('AA1', '配送时长');
            $objActSheet->setCellValue('AB1', '配送员取货时间');
            $objActSheet->setCellValue('AC1', '配送员开始配送时间');
            $objActSheet->setCellValue('AD1', '配送状态');
            $objActSheet->setCellValue('AE1', '接单类型');
            $objActSheet->setCellValue('AF1', '流水单号');
            $objActSheet->setCellValue('AG1', '订单备注');
            $objActSheet->setCellValue('AH1', '期望送达时间');

            $fied = "s.`tip_price`,s.`freight_charge`,m.`circle_id`, s.`supply_id`, s.`order_id`, ".
                "CASE  WHEN s.`server_type` = 0 THEN (SELECT expect_use_time FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id)  WHEN s.`server_type` = 1 THEN (SELECT fetch_time FROM pigcms_service_user_publish_give AS supg WHERE supg.publish_id = s.order_id)  ELSE (SELECT arrival_time_info FROM pigcms_service_user_publish_buy AS supb WHERE supb.publish_id = s.order_id) END AS expect_time, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT is_own FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT is_own FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS is_own, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_time FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT pay_time FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_pay_time, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_type FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT pay_type FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_pay_type, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT is_mobile_pay FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT is_mobile_pay FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_is_mobile_pay, ".
                "CASE  WHEN s.`server_type` <> 0 THEN (SELECT paid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') else (SELECT paid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_paid, ".
                "s.order_id, s.create_time,s.order_time, s.order_from, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.end_time,s.pay_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.distance, s.from_lat, s.aim_lat, s.from_lnt, s.aim_lnt, s.get_type, s.server_type ";

            $sql = "SELECT ".$fied." FROM " .
                C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
                C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
                C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
                C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN ".
                C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";


            $sql .= ' WHERE s.type=0';
            if ($province_id > 0) {
                $sql .= " AND s.province_id=" . $province_id;
            }
            if ($city_id > 0) {
                $sql .= " AND s.city_id=" . $city_id;
            }
            if ($area_id > 0) {
                $sql .= " AND s.area_id=" . $area_id;
            }
            // 判断需要查询的类型，做判断
            if (!empty($keyword)) {
                if ($searchtype == 'real_orderid') {
                    $order_sql = '(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn)';
                     $sql .= ' AND '.$order_sql.' = "' . $keyword . '"';
                } elseif ($_GET['searchtype'] == 'orderid') {
                    $orderid_sql = "(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id)";
                    $sql .= ' AND '.$orderid_sql.' = "' . $keyword . '"';
                } elseif ($searchtype == 's_name') {
                    $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                } elseif ($searchtype == 'name') {
                    $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                } elseif ($searchtype == 'phone') {
                    $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                } elseif ($searchtype == 'd_name') {
                    $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                }
            }
            if ($time > 0) {
                $sqltime = $time * 60;
                if ($timetype == 0) {
                    $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                } elseif ($timetype == 1) {
                    $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                } elseif ($timetype == 2) {
                    $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                } elseif ($timetype == 3) {
                    $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                }
            }
            if ($stime && $etime) {
                $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
            }
			if ($status != -1) {
				$sql .= " AND s.status='{$status}'";
			}
			if ($order_from > - 1) {
			    if ($order_from == 0) {
			        $sql .= " AND s.order_from<3";
			    } elseif ($order_from == 4) {
			        $sql .= " AND (s.order_from=4 OR s.order_from=5)";
			    } else {
			        $sql .= " AND s.order_from=" . $order_from;
			    }
			}
			if ($this->system_session['area_id']) {
			    $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
			    $sql .= ' AND m.' . $area_index . '=' . $this->system_session['area_id'];
			}
			
			$sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $i * 1000 . ', 1000';


			$supply_list = D()->query($sql);
			
			if (!empty($supply_list)) {
				import('ORG.Net.IpLocation');
				$IpLocation = new IpLocation();
				$index = 2;
				foreach ($supply_list as $value) {
				    $value['confirm_time'] = $value['create_time'] && $value['order_time'] ? intval(($value['create_time'] - $value['order_time']) / 60) . '分钟' : '-';
				    $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . '分钟' : '-';
				    $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . '分钟' : '-';
				    $where['order_id'] = $value['order_id'];
				    $where['status'] = array('in','4,5');
				    $times = D('Shop_order_log')->where($where)->select();

                    $third_id = '';
				    // 查询一下关联 快店的商品信息
                    $circle_info = '';
                    $order_from_info = '-'; // 订单来源 0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团
                    if ($value['item'] != 3 && $value['order_from'] != 3 && $value['order_from'] != 4 && $value['order_from'] != 5) {
                        $shop_detail = $m_shop_order_detail->where(array('order_id' => $value['order_id']))->field('name, num, price')->select();
                        $info = $m_shop_order->where(array('order_id' => $value['order_id']))->field('uid, desc, order_from')->find();
                        $userId = $info['uid'];
                        $remark = $info['desc'];
                        if ($info['order_from'] == 0) {
                            $order_from_info = L_('wapX1',['X1'=>$this->config['shop_alias_name']]);
                        } elseif($info['order_from'] == 1) {
                            $order_from_info = 'wap商城';
                        } elseif($info['order_from'] == 2) {
                            $order_from_info = 'Android';
                        } elseif($info['order_from'] == 3) {
                            $order_from_info = 'ios';
                        } elseif($info['order_from'] == 4) {
                            $order_from_info = '小程序';
                        } elseif($info['order_from'] == 5) {
                            $order_from_info = L_('pcX1',['X1'=>$this->config['shop_alias_name']]);
                        } elseif($info['order_from'] == 6) {
                            $order_from_info = '线下零售';
                        } elseif($info['order_from'] == 7) {
                            $order_from_info = '饿了么';
                        } elseif($info['order_from'] == 8) {
                            $order_from_info = '美团';
                        }elseif($info['order_from'] == 10) {
                            $order_from_info = $this->config['village_group_alias'];
                        }
                        if ($value['circle_id']) {
                            // 获取对应商圈信息
                            $condition_area = array(
                                'area_id' => $value['circle_id']
                            );
                            $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
                            if (!$circle_arr['area_ip_desc']) {
                                if ($circle_arr['area_pid']) {
                                    $condition_pid_area = array(
                                        'area_id' => $circle_arr['area_pid']
                                    );
                                    $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                                    $circle_info = $circle_msg . $circle_arr['area_name'];
                                } else {
                                    $circle_info = $circle_arr['area_name'];
                                }
                            } else {
                                $circle_info = $circle_arr['area_ip_desc'];
                            }
                        } else {
                            $circle_info = '';
                        }
                    } else {
                        // 帮我送
                        $userId = $m_service_user_publish->where(array('publish_id' => $value['order_id']))->getField('uid');
                        if ($value['order_from'] == 3) {
                            $remark = $m_service_user_publish_give->where(array('publish_id' => $value['order_id']))->getField('remarks');
                        } else {
                            // 帮我买
                            $remark = $m_service_user_publish_buy->where(array('publish_id' => $value['order_id']))->getField('goods_remarks');
                        }
                        $shop_detail = array();
                    }

                    $pay_time = $value['pay_time'] ? $value['pay_time'] : $value['order_pay_time'];
                    if ($shop_detail) {
                        $shop_first = false;
                        foreach ($shop_detail as $val) {
                            if (!$shop_first) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                                if ($value['item'] == 0) {
                                    $objActSheet->setCellValueExplicit('C' . $index, $this->config['meal_alias_name']);
                                } elseif ($value['item'] == 1) {
                                    $objActSheet->setCellValueExplicit('C' . $index, $this->config['waimai_alias_name']);
                                } else {
                                    if ($value['order_from'] == 0) {
                                        $objActSheet->setCellValueExplicit('C' . $index, $this->config['shop_alias_name']);
                                    } elseif ($value['order_from'] == 1) {
                                        $objActSheet->setCellValueExplicit('C' . $index, '饿了么');
                                    } elseif ($value['order_from'] == 2) {
                                        $objActSheet->setCellValueExplicit('C' . $index, '美团');
                                    } elseif ($value['order_from'] == 3) {
                                        $objActSheet->setCellValueExplicit('C' . $index, '帮我送');
                                    } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                        $objActSheet->setCellValueExplicit('C' . $index, '帮我买');
                                    }
                                }
                                $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                $objActSheet->setCellValueExplicit('F' . $index, $val['num']);


                                $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                                $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                                $objActSheet->setCellValueExplicit('J' . $index, $userId);
                                $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                                $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                                if ($value['order_paid'] == 1) {
                                    $objActSheet->setCellValueExplicit('M' . $index, '已支付');
                                } else {
                                    $objActSheet->setCellValueExplicit('M' . $index, '未支付');
                                }
                                $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                                $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                                $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                                $objActSheet->setCellValueExplicit('P' . $index, $pay_time? date('Y-m-d H:i:s',$pay_time) : '-');

                                $objActSheet->setCellValueExplicit('Q' . $index, sprintf("%.2f", floatval($value['money'])));
                                $objActSheet->setCellValueExplicit('R' . $index, sprintf("%.2f", floatval($value['deliver_cash'])));


                                $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                                $objActSheet->setCellValueExplicit('T' . $index, $value['phone'] . ' ');
                                $objActSheet->setCellValueExplicit('U' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : '还未开始');
                                $objActSheet->setCellValueExplicit('v' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : '还未完成');

                                $objActSheet->setCellValueExplicit('W' . $index, sprintf("%.2f",floatval($value['freight_charge'])));
                                $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f",floatval($value['tip_price'])));

                                $objActSheet->setCellValueExplicit('Y' . $index, $value['confirm_time']);
                                $objActSheet->setCellValueExplicit('Z' . $index, $value['grab_time']);
                                $objActSheet->setCellValueExplicit('AA' . $index, $value['deliver_use_time']);
                                if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                                    $objActSheet->setCellValueExplicit('AB' . $index, '-');
                                    $objActSheet->setCellValueExplicit('AC' . $index, '-');
                                    if ($value['third_id']) {
                                        $third_id = 'plat_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_'.$value['is_own'];
                                        }
                                    }
                                } else {
                                    $objActSheet->setCellValueExplicit('AB' . $index, $times[0]['dateline']? date('Y-m-d H:i:s',$times[0]['dateline']) : '还未开始');
                                    $objActSheet->setCellValueExplicit('AC' . $index, $times[1]['dateline']? date('Y-m-d H:i:s',$times[1]['dateline']) : '还未开始');
                                    if ($value['third_id']) {
                                        $third_id = 'shop_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_'.$value['is_own'];
                                        }
                                    }
                                }

                               switch ($value['status']) {
                                    case 1:
                                        $value['order_status'] = "等待接单";
                                        break;
                                    case 2:
                                        $value['order_status'] = "已接单";
                                        break;
                                    case 3:
                                        $value['order_status'] = "已取货";
                                        break;
                                    case 4:
                                        $value['order_status'] = "开始配送";
                                        break;
                                    case 5:
                                        $value['order_status'] = "已完成";
                                        break;
                                    case 6:
                                        $value['order_status'] = "已评价";
                                        break;
                                    case 7:
                                        $value['order_status'] = "退款退款失败";
                                        break;
                                    default:
                                        $value['order_status'] = "订单失效";
                                        break;
                                }
                                $objActSheet->setCellValueExplicit('AD' . $index, $value['order_status'] );
                                if($value['status']<2){
                                    $value['get_type'] = '--';
                                }elseif ($value['get_type']==0){
                                    $value['get_type'] = '抢单';
                                }else{
                                    $value['get_type'] = '系统派单';
                                }
                                $objActSheet->setCellValueExplicit('AE' . $index,  $value['get_type'] );
                                $objActSheet->setCellValueExplicit('AF' . $index,  $third_id );
                                $objActSheet->setCellValueExplicit('AG' . $index,  $remark );
                                $shop_first = true;
                                $index++;
                            }else {
                                $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                $objActSheet->setCellValueExplicit('F' . $index, $val['num']);
                                $index++;
                            }
                        }
                    } else {
                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                        if ($value['item'] == 0) {
                            $objActSheet->setCellValueExplicit('C' . $index, $this->config['meal_alias_name']);
                        } elseif ($value['item'] == 1) {
                            $objActSheet->setCellValueExplicit('C' . $index, $this->config['waimai_alias_name']);
                        } else {
                            if ($value['order_from'] == 0) {
                                $objActSheet->setCellValueExplicit('C' . $index, $this->config['shop_alias_name']);
                            } elseif ($value['order_from'] == 1) {
                                $objActSheet->setCellValueExplicit('C' . $index, '饿了么');
                            } elseif ($value['order_from'] == 2) {
                                $objActSheet->setCellValueExplicit('C' . $index, '美团');
                            } elseif ($value['order_from'] == 3) {
                                $objActSheet->setCellValueExplicit('C' . $index, '帮我送');
                            } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                $objActSheet->setCellValueExplicit('C' . $index, '帮我买');
                            }
                        }
                        $objActSheet->setCellValueExplicit('D' . $index, '-');
                        $objActSheet->setCellValueExplicit('E' . $index, '-');
                        $objActSheet->setCellValueExplicit('F' . $index, '-');


                        $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                        $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                        $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                        $objActSheet->setCellValueExplicit('J' . $index, $userId);
                        $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                        $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                        if ($value['order_paid'] == 1) {
                            $objActSheet->setCellValueExplicit('M' . $index, '已支付');
                        } else {
                            $objActSheet->setCellValueExplicit('M' . $index, '未支付');
                        }
                        $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                        $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                        $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                        $objActSheet->setCellValueExplicit('P' . $index, $pay_time? date('Y-m-d H:i:s',$pay_time) : '-');

                        $objActSheet->setCellValueExplicit('Q' . $index, sprintf("%.2f", floatval($value['money'])));
                        $objActSheet->setCellValueExplicit('R' . $index, sprintf("%.2f", floatval($value['deliver_cash'])));


                        $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                        $objActSheet->setCellValueExplicit('T' . $index, $value['phone'] . ' ');
                        $objActSheet->setCellValueExplicit('U' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : '还未开始');
                        $objActSheet->setCellValueExplicit('V' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : '还未完成');

                        $objActSheet->setCellValueExplicit('W' . $index, sprintf("%.2f",floatval($value['freight_charge'])));
                        $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f",floatval($value['tip_price'])));

                        $objActSheet->setCellValueExplicit('Y' . $index, $value['confirm_time']);
                        $objActSheet->setCellValueExplicit('Z' . $index, $value['grab_time']);
                        $objActSheet->setCellValueExplicit('AA' . $index, $value['deliver_use_time']);

                        if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                            $objActSheet->setCellValueExplicit('AB' . $index, '-');
                            $objActSheet->setCellValueExplicit('AC' . $index, '-');
                            if ($value['third_id']) {
                                $third_id = 'plat_' . $value['third_id'];
                                if ($value['is_own'] > 0) {
                                    $third_id .= '_'.$value['is_own'];
                                }
                            }
                        } else {
                            $objActSheet->setCellValueExplicit('AB' . $index, $times[0]['dateline']? date('Y-m-d H:i:s',$times[0]['dateline']) : '还未开始');
                            $objActSheet->setCellValueExplicit('AC' . $index, $times[1]['dateline']? date('Y-m-d H:i:s',$times[1]['dateline']) : '还未开始');
                            if ($value['third_id']) {
                                $third_id = 'shop_' . $value['third_id'];
                                if ($value['is_own'] > 0) {
                                    $third_id .= '_'.$value['is_own'];
                                }
                            }
                        }

                        switch ($value['status']) {
                            case 1:
                                $value['order_status'] = "等待接单";
                                break;
                            case 2:
                                $value['order_status'] = "已接单";
                                break;
                            case 3:
                                $value['order_status'] = "已取货";
                                break;
                            case 4:
                                $value['order_status'] = "开始配送";
                                break;
                            case 5:
                                $value['order_status'] = "已完成";
                                break;
                            case 6:
                                $value['order_status'] = "已评价";
                                break;
                            case 7:
                                $value['order_status'] = "退款退款失败";
                                break;
                            default:
                                $value['order_status'] = "订单失效";
                                break;
                        }
                        $objActSheet->setCellValueExplicit('AD' . $index, $value['order_status'] );
                        if($value['status']<2){
                            $value['get_type'] = '--';
                        }elseif ($value['get_type']==0){
                            $value['get_type'] = '抢单';
                        }else{
                            $value['get_type'] = '系统派单';
                        }
                        $objActSheet->setCellValueExplicit('AE' . $index,  $value['get_type'] );
                        $objActSheet->setCellValueExplicit('AF' . $index,  $third_id );
                        $objActSheet->setCellValueExplicit('AG' . $index,  $remark );
                        if ($value['server_type']==0) {
                            $objActSheet->setCellValueExplicit('AH' . $index, $value['expect_time'] ? date('Y-m-d H:i:s', $value['expect_time']) : '无');
                        }else{
                            $objActSheet->setCellValueExplicit('AH' . $index, $value['expect_time'] ? $value['expect_time'] : '无');
                        }

                        $index++;
                    }
				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
		
	}
	
	public function export_user()
	{
		set_time_limit(0);
		
		$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
		$begin_time = isset($_GET['begin_time']) ? htmlspecialchars($_GET['begin_time']) : '';
		$end_time = isset($_GET['end_time']) ? htmlspecialchars($_GET['end_time']) : '';
        $order_from = isset($_GET['order_from']) ? intval($_GET['order_from']) : -1;
		$condition_user = array('mer_id' => 0, 'uid' => $uid);
		$user = $this->deliver_user->field(true)->where($condition_user)->find();
		if (empty($user)) $this->error('不存在的配送员');
		
		
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		
		if ($begin_time && $end_time) {
			$title = '【' . $user['name'] . '】在' . $begin_time . '至' . $end_time . '时间段的配送记录列表';
		} else {
			$title = '【' . $user['name'] . '】的配送记录列表';
		}
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		
		// 设置当前的sheet
		$sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
		$sql_count .= ' WHERE s.type=0 AND s.uid=' . $uid;

        if ($order_from > - 1) {
            if ($order_from == 0) {
                $sql_count .= " AND s.order_from<3";
            } elseif ($order_from == 4) {
                $sql_count .= " AND (s.order_from=4 OR s.order_from=5)";
            } else {
                $sql_count .= " AND s.order_from=" . $order_from;
            }
        }

		if ($begin_time && $end_time) {
			$sql_count .= ' AND s.end_time>' . strtotime($begin_time) . ' AND s.end_time<' . strtotime($end_time);
		}
		
		$res_count = D()->query($sql_count);
		$count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
		
		$length = ceil($count_order / 1000);
		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
		
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千配送订单');
			$objActSheet = $objExcel->getActiveSheet();
				
			$objActSheet->setCellValue('A1', '配送ID');
			$objActSheet->setCellValue('B1', '订单来源');
			$objActSheet->setCellValue('C1', '店铺名称');
			$objActSheet->setCellValue('D1', '客户名称');
			$objActSheet->setCellValue('E1', '客户手机');
			$objActSheet->setCellValue('F1', '客户地址');
			$objActSheet->setCellValue('G1', '支付状态');
			$objActSheet->setCellValue('H1', '订单价格');
			$objActSheet->setCellValue('I1', '小费');
			$objActSheet->setCellValue('J1', '配送状态');
			$objActSheet->setCellValue('K1', '开始时间');
			$objActSheet->setCellValue('L1', '送达时间');
			$objActSheet->setCellValue('M1', '配送费');
            $objActSheet->setCellValue('N1', '平台服务费');
            $objActSheet->setCellValue('O1', '配送员佣金');
			$objActSheet->setCellValue('P1', '应收现金');
			
			
			$sql = "SELECT s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, s.start_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.freight_charge, s.tip_price , s.deliver_user_fee , (s.freight_charge - s.deliver_user_fee) as platform_fee FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
			$sql .= ' WHERE s.type=0 AND s.uid=' . $uid;
			if ($begin_time && $end_time) {
				$sql .= ' AND s.end_time>' . strtotime($begin_time) . ' AND s.end_time<' . strtotime($end_time);
			}
            if ($order_from > - 1) {
                if ($order_from == 0) {
                    $sql .= " AND s.order_from<3";
                } elseif ($order_from == 4) {
                    $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                } else {
                    $sql .= " AND s.order_from=" . $order_from;
                }
            }
			
			$sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $i * 1000 . ', 1000';
            
			$supply_list = D()->query($sql);
				
			if (!empty($supply_list)) {
				import('ORG.Net.IpLocation');
				$IpLocation = new IpLocation();
				$index = 2;
				foreach ($supply_list as $value) {
						
					$objActSheet->setCellValueExplicit('A' . $index, $value['supply_id']);
					if ($value['item'] == 0) {
						$objActSheet->setCellValueExplicit('B' . $index, $this->config['meal_alias_name']);
					} elseif ($value['item'] == 1) {
						$objActSheet->setCellValueExplicit('B' . $index, $this->config['waimai_alias_name']);
					} elseif ($value['item'] == 2) {
						$objActSheet->setCellValueExplicit('B' . $index, $this->config['shop_alias_name']);
					} elseif ($value['item'] == 3) {
                        $objActSheet->setCellValueExplicit('B' . $index, $value['server_type'] == 1 ? '帮我送' : '帮我买');
                    }
					$objActSheet->setCellValueExplicit('C' . $index, $value['storename']);
					$objActSheet->setCellValueExplicit('D' . $index, $value['username']);
					$objActSheet->setCellValueExplicit('E' . $index, $value['userphone'] . ' ');
					$objActSheet->setCellValueExplicit('F' . $index, $value['aim_site']);
					if ($value['paid'] == 1) {
						$objActSheet->setCellValueExplicit('G' . $index, '已支付');
					} else {
						$objActSheet->setCellValueExplicit('G' . $index, '未支付');
					}
						
					$objActSheet->setCellValueExplicit('H' . $index, floatval($value['money']));
					$objActSheet->setCellValueExplicit('P' . $index, floatval($value['deliver_cash']));
					switch ($value['status']) {
						case 1:
							$value['order_status'] = '<font color="red">等待接单</font>';
							break;
						case 2:
							$value['order_status'] = "接单";
							break;
						case 3:
							$value['order_status'] = "取货";
							break;
						case 4:
							$value['order_status'] = "开始配送";
							break;
						case 5:
							$value['order_status'] = "完成";
							break;
						case 6:
						    $value['order_status'] = "已评价";
						    break;
						default:
							$value['order_status'] = "订单失效";
							break;
					}	
					$objActSheet->setCellValueExplicit('J' . $index, $value['order_status']);
					$objActSheet->setCellValueExplicit('K' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : '--');
					$objActSheet->setCellValueExplicit('L' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : '--');
					$objActSheet->setCellValueExplicit('M' . $index, $value['freight_charge']);
                    $objActSheet->setCellValueExplicit('N' . $index, $value['platform_fee']);
                    $objActSheet->setCellValueExplicit('O' . $index, $value['deliver_user_fee']);
					$objActSheet->setCellValueExplicit('I' . $index, $value['tip_price']);
						
					$index++;
				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="' . $title . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}
	
	public function deliver_model(){
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
		$this->assign('city_list',$city_list);
		$this->display();
	}
	public function changeCityModel(){
		$deliver_model = isset($_GET['deliver_model']) ? intval($_GET['deliver_model']) : 0;
		$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : 0;
		if(empty($city_id)){
			$this->error('城市ID没带');
		}
		if(M('Area')->where(['area_id'=>$city_id])->data(['deliver_model'=>$deliver_model])->save() !== false){
			$this->success('保存成功');
		}else{
			$this->error('保存失败');
		}
	}
    
    public function desk()
    {
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $this->assign('type', $type);
        $data = array('is_province' => 1, 'is_city' => 1, 'is_area' => 1, 'is_street' => 1);
        $area_type = 0;
        $area_name = '';
        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            if ($area_index == 'province_id') {
                $data['province_id'] = $this->system_session['area_id'];
                
                $areas = D('Area')->field('area_id, area_pid')->where(array('area_pid' => $this->system_session['area_id'], 'is_open' => 1))->order('area_id ASC')->limit(1)->select();
                $data['city_id'] = $areas[0]['area_id'];
                $data['area_id'] = 0;
                
                $data['is_province'] = 0;
            } elseif ($area_index == 'city_id') {
                $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $this->system_session['area_id']))->find();
                $data['province_id'] = $area['area_pid'];
                $data['city_id'] = $this->system_session['area_id'];
                $data['area_id'] = 0;
                $data['is_province'] = 0;
                $data['is_city'] = 0;
            } else {
                $data['area_id'] = $this->system_session['area_id'];
                $area = D('Area')->field('area_name, area_id, area_pid')->where(array('area_id' => $this->system_session['area_id']))->find();
                $area_type = 3;
                $area_name = $area['area_name'];
                $data['city_id'] = $area['area_pid'];
                $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $data['city_id']))->find();
                $data['province_id'] = $area['area_pid'];
                $data['is_province'] = 0;
                $data['is_city'] = 0;
                $data['is_area'] = 0;
            }
			$data['system_area_index'] = $area_index;
        } else {
            if($_GET['city_id']){
				$area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $_GET['city_id']))->find();
			}else{
				$area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $this->config['now_city']))->find();
			}
            $data['province_id'] = $area['area_pid'];
            $data['city_id'] = $area['area_id'];
            $data['area_id'] = 0;
        }
        if ($area_id) {
            $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $area_id))->find();
            $data['area_id'] = $area_id;
            $data['city_id'] = $area['area_pid'];
            $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $data['city_id']))->find();
            $data['province_id'] = $area['area_pid'];
        }
        $this->assign(array('area_type' => $area_type, 'area_name' => $area_name));
        $data['lat'] = 0;
        $data['lng'] = 0;
        
        $condition_user = array('status' => 1, 'group' => 1);
        $condition_user['province_id'] = $data['province_id'];
        $condition_user['city_id'] = $data['city_id'];
        $condition_user['area_id'] = $data['area_id'];
        $userList = $this->deliver_user->field('uid, now_lng, now_lat, name, phone')->where($condition_user)->limit(1)->select();
        if ($userList && getFormatNumber($userList[0]['now_lat']) != '0') {
            $data['lat'] = $userList[0]['now_lat'];
            $data['lng'] = $userList[0]['now_lng'];
        }else if($data['city_id']){
			$now_city = M('Area')->where(array('area_id'=>$data['city_id']))->find();
			if(empty($now_city['area_lat'])){
				$result = D('Area')->address_info($now_city['area_name']);
				$lng = $result['result']['location']['lng'];
				$lat = $result['result']['location']['lat'];
				M('Area')->where(array('area_id'=>$data['city_id']))->data(array('area_lng'=>$lng,'area_lat'=>$lat))->save();
				$data['lat'] = $lat;
				$data['lng'] = $lng;
			}else{
				$data['lat'] = $now_city['area_lat'];
				$data['lng'] = $now_city['area_lng'];
			}
		}
		
		$data['deliver_model'] = M('Area')->where(['area_id'=>$data['city_id']])->getField('deliver_model');
        $this->assign($data);
        $this->display();
    }
	
	public function getCityUnGetData(){
        $city_id = I('city_id', 0);
		if(!$city_id){
			$this->error('城市ID没带');
		}
		$where = array('type' => 0,'status' => 1);
        $where['city_id'] = $city_id;
		$countList = D('Deliver_supply')->field('area_id')->where($where)->group('uid, status')->select();
		$countArr = array();
		foreach($countList as $value){
			if($countArr[$value['area_id']]){
				$countArr[$value['area_id']]++;
			}else{
				$countArr[$value['area_id']] = 1;
			}
		}
		if(!$countArr){
			$this->error('-1');
		}
		$this->success($countArr);
	}

    public function getData()
    {
        $province_id = I('province_id', 0);
        $city_id = I('city_id', 0);
        $area_id = I('area_id', 0);
        $street_id = I('street_id', 0,'intval');
		
		$this->timezone_set($city_id);
        
        $where = array('type' => 0);
        $where['province_id'] = $province_id;
        $where['city_id'] = $city_id;
        if ($area_id) {
            $where['area_id'] = $area_id;
        }
        if ($street_id) {
            $where['street_id'] = $street_id;
        }
        $where['item'] = ['neq',0];
        $condition_user = array('status' => 1, 'group' => 1);
        $condition_user['province_id'] = $province_id;
        $condition_user['city_id'] = $city_id;
		if($area_id){
			$condition_user['area_id'] = $area_id;
		}
        $condition_user['is_notice'] = 0;
        $condition_user['last_time'] = array('neq','0');
        
        $userList = $this->deliver_user->field('uid, lng, lat, now_lng, now_lat, name, phone, traffic')->where($condition_user)->select();
        
        $countList = D('Deliver_supply')->field('count(1) as cnt, status, uid')->where($where)->group('uid, status')->select();
        $unGetCount = 0;
        $sendCount = 0;
        $newList = array();
        foreach ($countList as $count) {
            switch ($count['status']) {
                case 0:
                    break;
                case 1:
                    $unGetCount += $count['cnt'];
                    break;
                case 2:
                    $newList[$count['uid']]['fetch'] += $count['cnt'];
                    $sendCount += $count['cnt'];
                    break;
                case 3:
                case 4:
                    $newList[$count['uid']]['send'] += $count['cnt'];
                    $sendCount += $count['cnt'];
                    break;
                case 5:
                    break;
            }
        }
        $where['status'] = array('in', array(5, 6));
		
		if(time() > strtotime(date('Y-m-d 04:30:00'))){
			$where['end_time'] = array('gt', strtotime(date('Y-m-d 04:30:00')));
		}else{
			$where['end_time'] = array('gt', strtotime(date('Y-m-d 04:30:00')) - 86400);
		}
        $finishList = D('Deliver_supply')->field('count(1) as cnt, uid')->where($where)->group('uid')->select();
        foreach ($finishList as $finish) {
            $newList[$finish['uid']]['finish'] = $finish['cnt'];
        }
        foreach ($userList as &$user) {
			if($user['now_lng'] == '0.000000'){
				$user['now_lng'] = $user['lng'];
				$user['now_lat'] = $user['lat'];
			}
			unset($user['lng'],$user['lat']);
			$user['traffic_name'] = $user['traffic'] ? $this->deliver_user->traffic[$user['traffic']]['text'] : '';
            $user['fetch'] = 0;
            $user['send'] = 0;
            $user['finish'] = 0;
            if (isset($newList[$user['uid']]['fetch'])) {
                $user['fetch'] = $newList[$user['uid']]['fetch'];
            }
            if (isset($newList[$user['uid']]['send'])) {
                $user['send'] = $newList[$user['uid']]['send'];
            }
            if (isset($newList[$user['uid']]['finish'])) {
                $user['finish'] = $newList[$user['uid']]['finish'];
            }
            if ($_GET['coordinate_type'] == 'wgs84') {
                list($user['now_lng'], $user['now_lat']) = Coordinate::bd09ToWgs84($user['now_lng'], $user['now_lat']);
            }
        }
        exit(json_encode(array('errcode' => false, 'data' => $userList, 'userCount' => count($userList), 'unGetCount' => $unGetCount, 'sendCount' => $sendCount)));
    }
    
    
    //加载带指派的订单
    public function initOrders()
    {
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $province_id = I('province_id', 0);
        $city_id = I('city_id', 0);
        $area_id = I('area_id', 0);
        $street_id = I('street_id', 0, 'intval');

		$deliver_model = M('Area')->where(['area_id'=>$city_id])->getField('deliver_model');
		if($deliver_model == 0){
			//exit(json_encode(array('errcode' => true, 'msg' => '配送员抢单模式中，地图调度暂不可用', 'deliver_model' => $deliver_model)));
		}
		if($deliver_model == 2){
			//exit(json_encode(array('errcode' => true, 'msg' => '智能调度模式中，地图调度暂不可用', 'deliver_model' => $deliver_model)));
		}
		
        $where = array('s.status' => 1, 's.type' => 0);
        $where['s.province_id'] = $province_id;
        $where['s.city_id'] = $city_id;
		if($area_id){
			$where['s.area_id'] = $area_id;
		}
		if($street_id){
            $where['s.street_id'] = $street_id;
        }
        $where['s.item'] = ['NEQ', 0];
        
        $time = time() + 3600 * $this->config['expected_duration_date'];

        $orderList = D('Deliver_supply')->alias('s')
            ->join('LEFT JOIN '.C('DB_PREFIX').'shop_order as o on s.order_id=o.order_id AND s.item=2')
            ->join('LEFT JOIN '.C('DB_PREFIX').'service_user_publish_buy as pb on s.order_id=pb.publish_id AND s.item=3')
            ->join('LEFT JOIN '.C('DB_PREFIX').'service_user_publish_give as pg on s.order_id=pg.publish_id AND s.item=3')
            ->join('LEFT JOIN '.C('DB_PREFIX').'mall_order as mo on s.order_id=mo.order_id AND s.item=4')
            ->field('s.*,o.expect_use_time,pb.arrival_time_info,pg.fetch_time,mo.express_send_time')->where($where)
            ->order('s.supply_id ASC')
            ->select();
        $nowTime = time();
        $nowList = array();
        $otherList = array();
        $zeroTime = strtotime(date('Y-m-d'));
        foreach ($orderList as $order) {
            if ($order['order_from'] > 2) {
                $order['store_name'] = $order['from_site'];
            }
            if ($order['item'] == 3) {
                $order['expect_use_date'] = is_null($order['arrival_time_info']) ? $order['fetch_time'] : $order['arrival_time_info'];
            } else if ($order['item'] == 4) {
                if ($order['express_send_time']) {
                    $order['expect_use_date'] = date('m/d H:i', explode(',', $order['express_send_time'])[0]);
                } else {
                    $order['expect_use_date'] = '';
                }
            }else {
                if ($order['expect_use_time'] == 0) {
                    $order['expect_use_date'] = '无';
                } else if ($order['expect_use_time'] >= $zeroTime) {
                    $order['expect_use_date'] = date('H:i', $order['expect_use_time']);
                } else {
                    $order['expect_use_date'] = date('m/d H:i', $order['expect_use_time']);
                }
            }

            $order['order_out_time'] = date('H:i', $order['order_out_time']);
            $order['desk_time'] = floor(($nowTime - $order['create_time'])/60);
            if ($deliver_model == 2) {
                if ($order['uid'] == 0 && ($order['expect_uid'] > 0 || $order['not_dispatch'] == 1)) {
                    //增加预指派快递员信息
					if($order['expect_uid']){
						$info = $this->deliver_user->find($order['expect_uid']);
						$info['traffic'] && $info['traffic_name'] = $this->deliver_user->traffic[$info['traffic']]['text'];
					}
					
                    if(date('Ymd',$order['expect_arrival_time']) == date('Ymd')){
                        $order['expect_arrival_short_date'] = date('H:i',strtotime($order['expect_arrival_time']));
                    }else{
                        $order['expect_arrival_short_date'] = date('m/d H:i',strtotime($order['expect_arrival_time']));
                    }
					
                    $info && $order['deliver_user_info'] = $info;
                    $otherList[] = $order;
                } else if ($order['uid'] == 0 && $order['expect_uid'] == 0) {
                    $nowList[] = $order;
                }
            } else {
                if ($order['appoint_time'] > $time) {
                    $otherList[] = $order;
                } else {
                    $nowList[] = $order;
                }
            }
        }

        if ($_GET['coordinate_type'] == 'wgs84') {
            foreach ($nowList as &$order) {
                list($order['aim_lnt'], $order['aim_lat']) = Coordinate::bd09ToWgs84($order['aim_lnt'], $order['aim_lat']);
                list($order['from_lnt'], $order['from_lat']) = Coordinate::bd09ToWgs84($order['from_lnt'], $order['from_lat']);
            }
            foreach ($otherList as &$order) {
                list($order['aim_lnt'], $order['aim_lat']) = Coordinate::bd09ToWgs84($order['aim_lnt'], $order['aim_lat']);
                list($order['from_lnt'], $order['from_lat']) = Coordinate::bd09ToWgs84($order['from_lnt'], $order['from_lat']);
            }
        }

        $rs = [
            'errcode' => false,
            'data' => $type == 0 ? $nowList : $otherList,
            'nowCount' => count($nowList),
            'otherCount' => count($otherList),
            'deliver_model' => $deliver_model
        ];
        exit(json_encode($rs));
    }
    
    //加载每个配送员待处理的订单
    public function initUserOrders()
    {
        $province_id = I('province_id', 0);
        $city_id = I('city_id', 0);
        $area_id = I('area_id', 0);
        $street_id = I('street_id', 0, 'intval');
        $uid = I('uid', 0);
        $where = array('s.status' => array('in', array(2, 3, 4)));
        $where['s.province_id'] = $province_id;
        $where['s.city_id'] = $city_id;
		if($area_id){
			$where['s.area_id'] = $area_id;
		}
        if($street_id){
            $where['s.street_id'] = $street_id;
        }
        if ($uid) {
            $where['s.uid'] = $uid;
        }
		$field = 's.*,o.real_orderid,o.expect_pick_time,u.traffic,pb.arrival_time_info,pg.fetch_time,mo.express_send_time';
        $orderList = M('Deliver_supply as s')
						->Join('LEFT JOIN '.C('DB_PREFIX').'shop_order as o on s.order_id=o.order_id AND s.item=2')
						->Join('LEFT JOIN '.C('DB_PREFIX').'deliver_user as u on s.uid=u.uid')
                        ->join('LEFT JOIN '.C('DB_PREFIX').'service_user_publish_buy as pb on s.order_id=pb.publish_id AND s.item=3')
                        ->join('LEFT JOIN '.C('DB_PREFIX').'service_user_publish_give as pg on s.order_id=pg.publish_id AND s.item=3')
                        ->join('LEFT JOIN '.C('DB_PREFIX').'mall_order as mo on s.order_id=mo.order_id AND s.item=4')
						->field($field)
						->where($where)
						->order('s.supply_id ASC')
						->select();
        $newList = [];
		//<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看{pigcms{$config.shop_alias_name}订单详情',900,'90%',true,false,false,false,'detail',true);">查看</a>
        foreach ($orderList as $order) {
			$order['expect_pick_time']   = $order['expect_pick_time']?date('Y-m-d H:i',$order['expect_pick_time']):'-';
			$order['start_time']   = date('Y-m-d H:i',$order['start_time']);
            if ($order['item'] == 3) {
                $order['appoint_time'] = is_null($order['arrival_time_info']) ? $order['fetch_time'] : $order['arrival_time_info'];
            } else {
                $order['appoint_time'] = date('Y-m-d H:i', $order['appoint_time']);
            }
            if (!$order['real_orderid']) {
                $order['real_orderid'] = $order['order_id'];
            }
            if (!$order['store_name']) {
                $order['store_name'] = '-';
            }


			switch ($order['traffic']) {
				case 'electric_bicycle':
                    $order['traffic'] = '电动自行车';
					break;
				case 'motorcycle':
                    $order['traffic'] = '摩托车';
					break;
				case 'car':
                    $order['traffic'] = '汽车';
					break;
				default:$order['traffic'] = 'unknow';
			}
			$order['order_detail_url'] = U('Shop/order_detail',array('order_id'=>$order['order_id'],'frame_show'=>true));
			$order['order_detail'] = '查看'.C('Config.shop_alias_name').'订单详情';

            if ($_GET['coordinate_type'] == 'wgs84') {
                list($order['aim_lnt'], $order['aim_lat']) = Coordinate::bd09ToWgs84($order['aim_lnt'], $order['aim_lat']);
                list($order['from_lnt'], $order['from_lat']) = Coordinate::bd09ToWgs84($order['from_lnt'], $order['from_lat']);
            }

            $newList[$order['uid']][] = $order;
        }
        exit(json_encode(array('errcode' => false, 'data' => $newList)));
    }
    // 调度控制台 操作
    public function appointDeliver()
    {
        $supply_ids = isset($_POST['supply_ids']) ? $_POST['supply_ids'] : 0;
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $user = D('Deliver_user')->field(true)->where(array('uid' => $uid, 'group' => 1, 'status' => 1))->find();
        if (empty($user)) {
            exit(json_encode(array('errcode' => true, 'msg' => '配送员不存在')));
        }
        $supplyArr = explode(',', $supply_ids);
        $supplys = D('Deliver_supply')->field(true)->where(array('supply_id' => array('in', $supplyArr), 'type' => 0))->select();
        if (empty($supplys)) {
            exit(json_encode(array('errcode' => true, 'msg' => '不存在的数据')));
        }

        $orderTypes = explode(',', $user['order_type']);
        $orderTypeNames = array_map(function ($r) {
            $names = [
                1 => '商家单',
                2 => '帮买单',
                3 => '帮送单',
                4 => '社区团购'
            ];
            return $names[$r] ?? '';
        }, $orderTypes);
        $orderTypeNames = implode('、', $orderTypeNames);
        foreach ($supplys as $s) {
            $checkOrderType = 0;
            if (in_array($s['item'], [2, 4, 5])) {
                $checkOrderType = 1;
            } else if ($s['item'] == 3 && in_array($s['server_type'], [2, 3])) {
                $checkOrderType = 2;
            } else if ($s['item'] == 3 && $s['server_type'] == 1) {
                $checkOrderType = 3;
            }else if ($s['item'] == 8){
                $checkOrderType = 4;
            }
            //判断配送员是否能接该类型订单
            if (empty($orderTypes) || !in_array($checkOrderType, $orderTypes)) {
                exit(json_encode(array('errcode' => true, 'msg' => '配送员只允许接' . $orderTypeNames)));
            }
        }

        $nowTime = time();
        foreach ($supplys as $sup) {
            if ($sup['status'] == 1) {
                $save_data = array('uid' => $uid, 'status' => 2, 'start_time' => $nowTime, 'get_type' => 1);
				$save_data['deliver_check'] = 0;
				$save_data['is_fetch_order'] = 0;
                $save_data['check_time'] = $nowTime + C('config.deliver_look_out_time') * 60;
			
                if ($sup['item'] == 0) {
                    $result = D("Meal_order")->where(array('order_id' => $sup['order_id']))->data(array('order_status' => 8))->save();
                } elseif ($sup['item'] == 2) {
                    $deliver_info = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
                    $result = D("Shop_order")->where(array('order_id' => $sup['order_id']))->data(array('order_status' => 2, 'deliver_info' => $deliver_info))->save();
                    D('Shop_order_log')->add_log(array('order_id' => $sup['order_id'], 'from_type' => 1, 'status' => 34, 'name' => $user['name'], 'phone' => $user['phone']));
                    $save_data['deliver_user_fee'] = $sup['freight_charge'] - ($sup['freight_charge'] * ($user['take_percent']/100));
                } elseif ($sup['item'] == 3) {
                    $offer_id = D('Service_offer')->add_offer($sup['order_id'], $uid, $this->system_session['id']);
                    $data = array('offer_id' => $offer_id);
                    if ($sup['server_type'] != 1 && !$sup['appoint_time']) {
                        $data['appoint_time'] = time() + $sup['server_time'] * 60;
                    }
                    D('Deliver_supply')->where(array('supply_id' => $sup['supply_id']))->save($data);
                    $publishInfo = D('Service_user_publish')->where(array('publish_id'=> $sup['order_id']))->find();
                    if($publishInfo['catgory_type'] == 2){
                        //帮我买
                        $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                    }
                    if($publishInfo['catgory_type'] == 3){
                        //帮我送
                        $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                    }
                    if($cat_field_info){
                        $fc = $cat_field_info['basic_distance_price'] + $cat_field_info['weight_price'] + $cat_field_info['distance_price'];//基础配送费+超重费用+超距离配送
                        $save_data['deliver_user_fee'] = $fc - ($fc * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
                    }
                    if (C('config.open_paotui_price_template')) {
                        $calDeliverUserFeeRs = invoke_v20_service('\app\paotui\model\service\ServiceUserPublishService/calDeliverUserFee', [$uid, array_merge($publishInfo, $cat_field_info)]);
                        $calDeliverUserFeeRs = $calDeliverUserFeeRs['data']['retval'] ?? [];
                        if (empty($calDeliverUserFeeRs)) {
                            $this->error('配送员所得费用计算失败,请重试');
                        }
                        $save_data['deliver_user_fee'] = $calDeliverUserFeeRs['total_fee'];
                    }
                } elseif ($sup['item'] == 4){
                    $save_data['deliver_user_fee'] = $sup['freight_charge'] - ($sup['freight_charge'] * ($user['take_percent']/100));//配送员所得费用（扣除平台抽成）
                }
                $result = D('Deliver_supply')->where(array('supply_id' => $sup['supply_id']))->save($save_data);
                D('Deliver_supply')->sendNotice($user, $sup);
            }
        }
        exit(json_encode(array('errcode' => false, 'msg' => 'ok')));
    }
    
    public function changeModel()
    {
		$deliver_model = isset($_GET['model']) ? intval($_GET['model']) : 0;
		$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : 0;
		if(!empty($city_id)){
			M('Area')->where(['area_id'=>$city_id])->data(['deliver_model'=>$deliver_model])->save();
		}
		exit(json_encode(array('errcode' => false, 'msg' => 'ok')));
    }
    
    public function order()
    {
        $area_id = intval($_GET['area_id']);
        $city_id = intval($_GET['city_id']);
        
        if ($area_id) {
            $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $area_id))->find();
            $data['area_id'] = $area_id;
            $city_id = $area['area_pid'];
            $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $city_id))->find();
            $province_id = $area['area_pid'];
        }else if($city_id){
            $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $city_id))->find();
            $province_id = $area['area_pid'];
		}
        if (!$area_id && !$city_id && 0 == $this->config['many_city'] && $this->config['now_city']>0) {
            // 没有参数，单城市获取单城市信息
            $area = D('Area')->field(true)->where(array('area_id' => $this->config['now_city']))->find();
            if ($area['area_type']==1) {
                $province_id = $area['area_id'];
            } elseif ($area['area_type']==2) {
                $city_id = $area['area_id'];
                $area = D('Area')->field('area_id, area_pid')->where(array('area_id' => $city_id))->find();
                $province_id = $area['area_pid'];
            } elseif ($area['area_type']==3) {
                $area_id = $area['area_id'];
            }
        }

        
        $sql = "SELECT s.`supply_id`, s.order_id,s.uid, s.item,s.is_right_now, s.name as username, s.phone as userphone, m.name as storename, s.money,s.appoint_time, u.name,u.traffic, u.phone, s.start_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.distance, s.from_lat, s.aim_lat, s.from_lnt, s.aim_lnt, s.get_type, s.server_type,s.order_from, s.is_fetch_order,o.expect_pick_time FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";
		$sql .= " LEFT JOIN " . C('DB_PREFIX') . "shop_order AS o on o.order_id=s.order_id AND s.item=2";
        $sql_count = "SELECT count(1) AS count,sum(s.freight_charge) AS total_freight,sum(s.money) AS total_money,sum(s.tip_price) AS tip_price FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";

		if($area_id) {
            $sql .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id . ' AND s.city_id=' . $city_id . ' AND s.area_id=' . $area_id;
            $sql_count .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id . ' AND s.city_id=' . $city_id . ' AND s.area_id=' . $area_id;
        }else if($city_id){
			$sql .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id . ' AND s.city_id=' . $city_id;
			$sql_count .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id . ' AND s.city_id=' . $city_id;
		}else if($province_id){
            $sql .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id ;
            $sql_count .= ' WHERE s.type=0 AND s.status> 1 AND s.status<5 AND s.province_id=' . $province_id;
        }

        import('@.ORG.system_page');
        $res_count = D()->query($sql_count);
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);
        
        foreach ($supply_info as &$value) {
			// $value['appoint_time'] = $value['appoint_time'] ?date("Y-m-d H:i:s", $value['appoint_time']):'-';
            if ($value['appoint_time']) {

                if ($value['is_right_now']) {
                    $value['appoint_time'] = '<span style="color:red;">'.L_('立即送达').' </span>'.date('H:i', $value['appoint_time'])  ;
                }else{
                    $value['appoint_time'] = date('Y-m-d H:i:s', $value['appoint_time']);
                }
            } else {
                $value['appoint_time'] = '-';
            }
            if(empty($value['appoint_time'])){

            }
            $value['expect_pick_time'] = $value['expect_pick_time'] ?date("Y-m-d H:i:s", $value['expect_pick_time']):'-';
            $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time']-$value['start_time'])/60).'分钟' : '-';
            $value['start_time'] = $value['start_time'] ? date("Y-m-d H:i:s", $value['start_time']) : '-';
            $value['end_time'] = $value['end_time'] ? date("Y-m-d H:i:s", $value['end_time']) : '-';
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
            $value['paid'] = $value['paid'] == 1 ? "已支付" : "未支付";
            $value['pay_type'] = $value['pay_type'] == "offline" ? "线下支付" : "线上支付";
            $value['distance'] = $value['distance'] ? $value['distance'] . 'km' : getRange(getDistance($value['from_lat'], $value['from_lnt'], $value['aim_lat'], $value['aim_lnt']));
            //订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            //配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            switch ($value['status']) {
                case 1:
                    $value['order_status'] = '<font color="red">等待接单</font>';
                    break;
                case 2:
                    if($value['is_fetch_order']){
                        $value['order_status'] = "已接单";
                    }else{
                        $value['order_status'] = '<font color="red">等待接单</font>';
                    }
                    break;
                case 3:
                    $value['order_status'] = "已取货";
                    break;
                case 4:
                    $value['order_status'] = "开始配送";
                    break;
                case 5:
                    $value['order_status'] = "已完成";
                    break;
                case 6:
                    $value['order_status'] = "已评价";
                    break;
                default:
                    $value['order_status'] = "订单失效";
                    break;
            }
			switch ($value['traffic']) {
				case 'electric_bicycle':
                    $value['traffic'] = '电动自行车';
					break;
				case 'motorcycle':
                    $value['traffic'] = '摩托车';
					break;
				case 'car':
                    $value['traffic'] = '汽车';
					break;
				default:$value['traffic'] = 'unknow';
			}
        }
        
        $pagebar = $p->show();
        $this->assign(array('status' => $status, 'day' => $day, 'period' => $period, 'phone' => $phone));
        $this->assign('selectStoreId', $selectStoreId);
        $this->assign('orderNum', $orderNum);
        $this->assign('selectUserId', $selectUserId);
        $this->assign('stores', $stores);
        $this->assign('delivers', $delivers);
        $this->assign('pagebar', $pagebar);
        $this->assign('supply_info', $supply_info);
        $this->assign('res_count', $res_count);
        $this->assign(array('area_id' => $area_id, 'city_id' => $city_id));
        $this->display();
    }
    
    public function reply()
    {
        $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
        $where = ' WHERE s.type=0 AND s.status>4';
        if (! empty($keyword)) {
            if ($_GET['nameType'] == 0) {
                $where .= " AND u.name LIKE '%" . $keyword . "%'";
            } elseif ($_GET['nameType'] == 1) {
                $where .= " AND s.name LIKE '%" . $keyword . "%'";
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $where .= ' AND u.area_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $where .= ' AND u.city_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $where .= ' AND u.province_id=' . $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
        if ($_GET['province_idss'] && $this->config['many_city']) {
            $where .= ' AND u.province_id=' . $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $where .= ' AND u.city_id=' . $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $where .= ' AND u.area_id=' . $_GET['area_id'];
        }
        $select = isset($_GET['select']) ? $_GET['select'] : '';
        $stime = $etime = 0;
        $day = 0;
        $period = 0;
        if (is_numeric($select)) {
            $day = $select;
            $stime = strtotime("-{$day} day");
            $etime = time();
        } else {
            $period = $select;
            $time_array = explode('-', $select);
            $stime = strtotime($time_array[0]);
            $etime = strtotime($time_array[1]) + 86400;
        }
        if ($stime && $etime) {
            $where .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
        }

        $comment = isset($_GET['comment']) ? $_GET['comment'] : 0;
        if ($comment == 1) {
            $where .= " AND s.score = 0 ";
        } else if ($comment == 2) {
            $where .= " AND s.score >=4 AND s.score <=5 ";
        } else if ($comment == 3) {
            $where .= " AND s.score =3 ";
        } else if ($comment == 4) {
            $where .= " AND s.score >=1 AND s.score <=2 ";
        }

        $sql = "SELECT s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.score, u.name, u.phone, s.comment, s.comment_time, s.from_site";
        $sql .= " FROM " . C('DB_PREFIX') . "merchant_store AS m ";
        $sql .= "RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id ";
        $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;
        
        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;

        $sql_tongji = "SELECT count(1) AS count, s.score FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where . ' group by s.score';
        

        import('@.ORG.system_page');
        $res_count = D()->query($sql_count);
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);
        foreach ($supply_info as &$value) {
            switch ($value['score']) {
                case 0:
                    $value['score'] = '未评论';
                    break;
                case 1:
                case 2:
                    $value['score'] .= '星（差评）';
                    break;
                case 3:
                    $value['score'] .= '星（中评）';
                    break;
                case 4:
                case 5:
                    $value['score'] .= '星（好评）';
                    break;
            }
        }
        
        $pagebar = $p->show();
        $this->assign(array('day' => $day, 'period' => $period, 'keyword' => $keyword));
        
        $tempList = D()->query($sql_tongji);
        
        $counts = array('commentScore0' => 0, 'commentScore1' => 0, 'commentScore2' => 0, 'commentScore3' => 0, 'count' => $count_order);
        $totalScore = 0;
        $total = 0;
        foreach ($tempList as $temp) {
            $totalScore += $temp['score'] * $temp['count'];
            if ($temp['score'] > 0) {
                $total += $temp['count'];
            }
            switch ($temp['score']) {
                case 0:
                    $counts['commentScore0'] += $temp['count'];
                    break;
                case 1:
                case 2:
                    $counts['commentScore1'] += $temp['count'];
                    break;
                case 3:
                    $counts['commentScore2'] += $temp['count'];
                    break;
                case 4:
                case 5:
                    $counts['commentScore3'] += $temp['count'];
                    break;
            }
        }
        if($total){
            $counts['meanScore'] = round($totalScore / $total, 2);
            
            $counts['goodPrecent'] = round($counts['commentScore3'] / $total * 100, 2) . '%';
        }
        else{
            $counts['meanScore'] = 0;
            $counts['goodPrecent'] = '0%';
        }

        $show_merchant_user = 0;
        if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
        
        $this->assign('pagebar', $pagebar);
        $this->assign('supply_info', $supply_info);
        $this->assign($counts);
        $this->display();
    }
    
    
    
    public function exportComment()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '配送员评论列表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        
        $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
        $where = ' WHERE s.type=0 AND s.status>4';
        if (! empty($keyword)) {
            if ($_GET['nameType'] == 0) {
                $where .= " AND u.name LIKE '%" . $keyword . "%'";
            } elseif ($_GET['nameType'] == 1) {
                $where .= " AND s.name LIKE '%" . $keyword . "%'";
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $where .= ' AND u.area_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $where .= ' AND u.city_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $where .= ' AND u.province_id=' . $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
        if ($_GET['province_idss'] && $this->config['many_city']) {
            $where .= ' AND u.province_id=' . $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $where .= ' AND u.city_id=' . $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $where .= ' AND u.area_id=' . $_GET['area_id'];
        }
        
        $day = I('day', 0, 'intval');
        $period = I('period', '', 'htmlspecialchars');
        $stime = $etime = 0;
        if ($day) {
            $stime = strtotime("-{$day} day");
            $etime = time();
        }
        if ($period) {
            $time_array = explode('-', $period);
            $stime = strtotime($time_array[0]);
            $etime = strtotime($time_array[1]) + 86400;
        }
        if ($stime && $etime) {
            $where .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
        }
        
        
        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;
        $res_count = D()->query($sql_count);
        $count = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            
            $objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千配送订单');
            $objActSheet = $objExcel->getActiveSheet();
            
            $objActSheet->setCellValue('A1', '配送ID');
            $objActSheet->setCellValue('B1', '店铺名称');
            $objActSheet->setCellValue('C1', '配送员昵称');
            $objActSheet->setCellValue('D1', '配送员手机号');
            $objActSheet->setCellValue('E1', '评价用户昵称');
            $objActSheet->setCellValue('F1', '评价用户手机号');
            $objActSheet->setCellValue('G1', '评论时间');
            $objActSheet->setCellValue('H1', '评分');
            $objActSheet->setCellValue('I1', '评论内容');
            $objActSheet->setCellValue('J1', '订单编号');
            
            $sql = "SELECT  case  when s.`server_type` <> 0 then sp.`order_sn` else so.real_orderid end as order_sn,s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.score, u.name, u.phone, s.comment, s.comment_time, s.from_site";
            $sql .= " FROM " . C('DB_PREFIX') . "merchant_store AS m ";
            $sql .= "RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id ";
            $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid ";
            $sql .= "LEFT JOIN " . C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) ";
            $sql .= "LEFT JOIN " . C('DB_PREFIX') . "shop_order AS so ON (so.order_id=s.order_id AND s.`server_type` = 0)" . $where;


            $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $i * 1000 . ', 1000';
            $supply_list = D()->query($sql);

            if (!empty($supply_list)) {
                import('ORG.Net.IpLocation');
                $IpLocation = new IpLocation();
                $index = 2;
                foreach ($supply_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['supply_id']);
                    if ($value['item'] == 3) {
                        $objActSheet->setCellValueExplicit('B' . $index, $value['from_site']);
                    } else {
                        $objActSheet->setCellValueExplicit('B' . $index, $value['storename']);
                    }
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['username'] . ' ');
                    $objActSheet->setCellValueExplicit('F' . $index, $value['userphone']);
                    if ($value['comment_time'] > 0) {
                        $objActSheet->setCellValueExplicit('G' . $index, date('Y-m-d H:i:s', $value['comment_time']));
                    } else {
                        $objActSheet->setCellValueExplicit('G' . $index, '--');
                    }
                    switch ($value['score']) {
                        case 0:
                            $value['score'] = '未评论';
                            break;
                        case 1:
                        case 2:
                            $value['score'] .= '星（差评）';
                            break;
                        case 3:
                            $value['score'] .= '星（中评）';
                            break;
                        case 4:
                        case 5:
                            $value['score'] .= '星（好评）';
                            break;
                    }
                    $objActSheet->setCellValueExplicit('H' . $index, $value['score']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['comment']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['order_sn']);

                    $index++;
                }
            }
            sleep(2);
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
        
    }
    
    /**
     * 自定义配送区域列表
     */
    public function area()
    {
        $provinceId = isset($_GET['province_id'])?intval($_GET['province_id']):'';
        $cityId = isset($_GET['city_id'])?intval($_GET['city_id']):'';
        $areaId = I('get.area_id',0,'intval');
        $streetId = I('get.street_id',0,'intval');
        $where = [];
        if ($cityId > 0) {
            $where['area_id'] = $cityId;
        } else if ($this->system_session['area_id']) {
            $sessionCityIds = D('Area')->getCityBuyAreaId($this->system_session['area_id']);
            $sessionCityIds && $where['area_id'] = $sessionCityIds[0];
        }
        $areaId > 0 && $where['distinct_id'] = $areaId;
        $streetId > 0 && $where['street_id'] = $streetId;

        $customs = D('Deliver_custom')->where($where)->field(true)->select();
        foreach ($customs as &$value) {
            if (C('config.tianditu_web_key')) {
                $value['delivery_range_polygon'] = polygon_2_type($value['delivery_range_polygon'], 'json', 'bd09ToWgs84');
                list($value['lng'], $value['lat']) = Coordinate::bd09ToWgs84($value['lng'], $value['lat']);
            } else {
                $value['delivery_range_polygon'] = polygon_2_type($value['delivery_range_polygon'], 'json');
            }
            
            if ($value['area_id'] == 0) {
                $value['area_name'] = '通用';
            } else {
                $areaName = M('Area')->where(['area_id' => $value['area_id']])->getField('area_name');
                $value['area_name'] = $areaName ?: '未知城市';
            }
        }
        $this->assign('customs', $customs);
        $this->display();
    }
    
    /**
     * 操作处理自定义配送区域
     */
    public function custom()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($custom = D('Deliver_custom')->field(true)->where(array('id' => $id))->find()) {
            if ($custom['delivery_range_polygon']) {
                if (C('config.tianditu_web_key')) {
                    $custom['delivery_range_polygon'] = polygon_2_type($custom['delivery_range_polygon'], 'json', 'bd09ToWgs84');
                    list($custom['lng'], $custom['lat']) = Coordinate::bd09ToWgs84($custom['lng'], $custom['lat']);
                } else {
                    $custom['delivery_range_polygon'] = polygon_2_type($custom['delivery_range_polygon'], 'json');
                }
            }
            if ($custom['lng'] && $custom['lat']) {
                $custom['lng_lat'] = $custom['lng'] . ',' . $custom['lat'];
            }
            if ($custom['area_id'] > 0) {
                $custom['area_info'] = M('Area')->where(['area_id' => $custom['area_id']])->field('area_id,area_pid,area_type')->find();
            }
        }
        $def_city = $this->system_session['area_id'] ? : $this->config['now_city'];
        $defaultCity = M('Area')->where(array('area_id'=>$def_city))->find();
        if($defaultCity){
            $defaultPos = ['lng'=>$defaultCity['area_lng'],'lat'=>$defaultCity['area_lat']];
            $this->assign('defaultPos', $defaultPos);
        }
        $this->assign('custom', $custom);
        $this->display();
    }
    
    /**
     * 保存配送自定义区域
     */
    public function customSave()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
        $delivery_range_polygon = isset($_POST['delivery_range_polygon']) ? htmlspecialchars(trim($_POST['delivery_range_polygon'])) : '';
        $lng_lat = isset($_POST['lng_lat']) ? htmlspecialchars(trim($_POST['lng_lat'])) : '';
        $areaId = isset($_POST['city_idss'])?intval($_POST['city_idss']):0;
        $distinct_id = isset($_POST['area_idss']) ? intval($_POST['area_idss']) : 0;
        $street_id = isset($_POST['street_id']) ? intval($_POST['street_id']) : 0;


        if (empty($name)) {
            $this->error('配送区域名称不能为空');
        }
        if (empty($delivery_range_polygon)) {
            $this->error('请绘制配送区域');
        }
        if (empty($lng_lat)) {
            $this->error('请绘制配送区域');
        }
        if($areaId <= 0){
            $this->error('配送区域所属城市');
        }
        $data = array();
        $data['name'] = $name;
        $data['area_id'] = $areaId;
        $data['distinct_id'] = $distinct_id;
        $data['street_id'] = $street_id;
        
        $latLngArray = explode('|', $delivery_range_polygon);
        if (count($latLngArray) < 3) {
            $this->error('请绘制一个合理的服务范围！');
        } else {
            $latLngData = array();
            foreach ($latLngArray as $row) {
                $latLng = explode(',', $row);
                if (C('config.tianditu_web_key')) {
                    list($latLng[1], $latLng[0]) = Coordinate::wgs84ToBd09($latLng[1], $latLng[0]);
                }
                $latLngData[] = $latLng[1] . ' ' . $latLng[0];
            }
            $latLngData[] = $latLngData[0];
            $data['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
        }
        $lngLat = explode(',', $lng_lat);
        $data['lng'] = $lngLat[0];
        $data['lat'] = $lngLat[1];
        if (C('config.tianditu_web_key')) {
            list($data['lng'], $data['lat']) = Coordinate::wgs84ToBd09($data['lng'], $data['lat']);
        }
        $data['last_time'] = time();
        if ($custom = D('Deliver_custom')->field(true)->where(array('id' => $id))->find()) {
            if (D('Deliver_custom')->where(array('id' => $id))->save($data)) {
                D('Merchant_store_shop')->where(array('custom_id' => $id))->save(array('delivery_range_polygon' => $data['delivery_range_polygon']));

                //修改配送区域成功，则快递员关联的也同步修改
                if ($data['delivery_range_polygon'] != $custom['delivery_range_polygon']) {
                    $this->deliver_user->where(['delivery_range_type' => 1, 'custom_id' => $id])->save(['delivery_range_polygon' => $data['delivery_range_polygon']]);
                }
                $this->success('修改配送区域成功');
            } else {
                $this->error('修改配送区域失败');
            }
        } else {
            if (D('Deliver_custom')->add($data)) {
                $this->success('添加配送区域成功');
            } else {
                $this->error('添加配送区域失败');
            }
        }
    }
    
    /**
     * 删除自定义配送区域
     */
    public function customDel()
    {
        $id = intval($_POST['id']);
        if (D('Deliver_custom')->where(array('id' => $id))->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败！请重试~');
        }
    }
    
    public function cancel()
    {
        $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
        $where = ' WHERE ds.type=0 AND ds.status>=1';
        if (! empty($keyword)) {
            if ($_GET['nameType'] == 0) {
                $where .= " AND u.name LIKE '%" . $keyword . "%'";
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $where .= ' AND u.area_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $where .= ' AND u.city_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $where .= ' AND u.province_id=' . $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
        if ($_GET['province_idss'] && $this->config['many_city']) {
            $where .= ' AND u.province_id=' . $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $where .= ' AND u.city_id=' . $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $where .= ' AND u.area_id=' . $_GET['area_id'];
        }
        $select = isset($_GET['select']) ? $_GET['select'] : '';
        $stime = $etime = 0;
        $day = 0;
        $period = 0;
        if (is_numeric($select)) {
            $day = $select;
            $stime = strtotime("-{$day} day");
            $etime = time();
        } else {
            $period = $select;
            $time_array = explode('-', $select);
            $stime = strtotime($time_array[0]);
            $etime = strtotime($time_array[1]) + 86400;
        }
        if ($stime && $etime) {
            $where .= " AND s.dateline>'{$stime}' AND s.dateline<'{$etime}'";
        }

        $sql = "SELECT s.`supply_id`, m.name as storename, s.item, u.name, u.phone, s.order_id, s.dateline";
        $sql .= " FROM " . C('DB_PREFIX') . "merchant_store AS m ";
        $sql .= "RIGHT JOIN " . C('DB_PREFIX') . "deliver_cancel_log AS s ON m.store_id=s.store_id ";
        $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_supply AS ds ON ds.supply_id=s.supply_id ";
        $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;

        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_cancel_log AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_supply AS ds ON ds.supply_id=s.supply_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;

        $sql_tongji = "SELECT count(1) AS count, score FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_cancel_log AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where . ' group by s.score';
        
        import('@.ORG.system_page');
        $res_count = D()->query($sql_count);
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);
        
        $pagebar = $p->show();
        $this->assign(array('day' => $day, 'period' => $period, 'keyword' => $keyword));

        $show_merchant_user = 0;
        if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
        
        $this->assign('pagebar', $pagebar);
        $this->assign('supply_info', $supply_info);
        $this->assign($counts);
        
        $this->display();
    }
    
    
    public function exportCancel()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '配送员扔回订单列表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        
        $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
        $where = ' WHERE ds.type=0 AND ds.status>=1';
        if (! empty($keyword)) {
            if ($_GET['nameType'] == 0) {
                $where .= " AND u.name LIKE '%" . $keyword . "%'";
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $where .= ' AND u.area_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $where .= ' AND u.city_id=' . $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $where .= ' AND u.province_id=' . $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }
        if ($_GET['province_idss'] && $this->config['many_city']) {
            $where .= ' AND u.province_id=' . $_GET['province_idss'];
        }
        if ($_GET['city_idss']) {
            $where .= ' AND u.city_id=' . $_GET['city_idss'];
        }
        if ($_GET['area_id']) {
            $where .= ' AND u.area_id=' . $_GET['area_id'];
        }
        
        $day = I('day', 0, 'intval');
        $period = I('period', '', 'htmlspecialchars');
        $stime = $etime = 0;
        if ($day) {
            $stime = strtotime("-{$day} day");
            $etime = time();
        }
        if ($period) {
            $time_array = explode('-', $period);
            $stime = strtotime($time_array[0]);
            $etime = strtotime($time_array[1]) + 86400;
        }
        if ($stime && $etime) {
            $where .= " AND s.dateline>'{$stime}' AND s.dateline<'{$etime}'";
        }


        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_cancel_log AS s ON m.store_id=s.store_id  LEFT JOIN " . C('DB_PREFIX') . "deliver_supply AS ds ON ds.supply_id=s.supply_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;
        $res_count = D()->query($sql_count);
        $count = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;
        
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            
            $objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千配送订单');
            $objActSheet = $objExcel->getActiveSheet();
            
            $objActSheet->setCellValue('A1', '配送ID');
            $objActSheet->setCellValue('B1', '店铺名称');
            $objActSheet->setCellValue('C1', '配送员昵称');
            $objActSheet->setCellValue('D1', '配送员手机号');
            $objActSheet->setCellValue('E1', '扔回时间');

            $sql = "SELECT s.`supply_id`, s.order_id, s.item, m.name as storename, ds.score, u.name, u.phone, ds.comment, s.dateline, ds.from_site";
            $sql .= " FROM " . C('DB_PREFIX') . "merchant_store AS m ";
            $sql .= "RIGHT JOIN " . C('DB_PREFIX') . "deliver_cancel_log AS s ON m.store_id=s.store_id ";
            $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_supply AS ds ON ds.supply_id=s.supply_id ";
            $sql .= "LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid" . $where;
            $sql .= ' ORDER BY s.`id` DESC LIMIT ' . $i * 1000 . ', 1000';
            $supply_list = D()->query($sql);
            
            if (!empty($supply_list)) {
                $index = 2;
                foreach ($supply_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['supply_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['storename']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
                    if ($value['dateline'] > 0) {
                        $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['dateline']));
                    } else {
                        $objActSheet->setCellValueExplicit('E' . $index, '--');
                    }
                    $index++;
                }
            }
            sleep(2);
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function moni_reply_money() {
        $order_id = '21985';
        $supply = D('Deliver_supply')->field(true)->where(array('supply_id' => $order_id, 'type' => 0))->find();
        fdump($supply, '$supply', true);
        // 进行原路退款
        $refund = D('Plat_order')->order_refund(array('business_type' => 'service', 'business_id' => $order_id));
        fdump($refund, '$refund', true);
        if (!$refund['error']) {
            D('Service_user_publish')->where(array('publish_id' => $order_id))->save(array('status' => 6));
            D('Service_offer')->where(array('publish_id' => $order_id))->save(array('status' => 6));
            D('Deliver_supply')->updateStatusToZero($order_id);
            $now_offer = D('Service_offer')->where(array('publish_id' => $order_id))->find();
            D("Service_offer_record")->add(array('offer_id' => $now_offer['offer_id'], 'publish_id' => $order_id, 'add_time' => time(), 'remarks' => '退款成功'));
            // 推送模板消息
            $now_order = D('Plat_order')->where(array('business_type' => 'service', 'business_id' => $order_id))->find();
            $now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
            $order_sn = D('Service_user_publish')->where(array('publish_id' => $order_id))->getField('order_sn');
            fdump($now_user, '$now_user', true);
            if ($now_user['openid']) {
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $order_id;
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' => '您的跑腿订单已取消，系统已自动退款！',
                    'OrderSn' => $order_sn,
                    'OrderStatus' => '已完成退款',
                    'remark' => date('Y-m-d H:i:s')
                ));
            }
            $deliver_user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();
            fdump($deliver_user, '$deliver_user', true);
            if ($deliver_user['openid']) {
                $href = '';
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $deliver_user['openid'],
                    'first' => '用户取消跑腿订单！',
                    'OrderSn' => $order_sn,
                    'OrderStatus' => '已取消',
                    'remark' => date('Y-m-d H:i:s')
                ));
            }
        }
    }
	
	public function off_work(){
		if(empty($_POST['uid'])){
			$this->error('配送员ID没有带');
		}
		
		if(M('Deliver_user')->where(['uid' => intval($_POST['uid']),'status'=>array('neq',4)])->data(['is_notice' => '1'])->save() !== false){
			$user = M('Deliver_user')->where(['uid' => intval($_POST['uid']),'status'=>array('neq',4)])->find();
			D('Deliver_supply')->deliver_off_work($user);
			$this->success('关闭接单成功');
		}else{
			$this->error('关闭接单失败');
		}
	}

    /**
     * 调度模式设置
     * @author: 张涛
     * @date: 2020/10/20
     */
    public function setting()
    {
        $mod = M('Area');
        if (IS_POST) {
           
            $areaId = $_POST['area_id'] ?? 0;
            $areaInfo = $mod->where(['area_id' => $areaId])->find();
            if (empty($areaInfo)) {
                $this->error('地区不存在');
            }
            $mod->where(['area_id' => $areaId])->save($_POST);
            $this->success('设置成功');
        } else {
            $areaId = I('get.area_id', 0, 'intval');
            $areaInfo = $mod->where(['area_id' => $areaId])->find();
            $this->assign('areaInfo', $areaInfo);
            $this->display();
        }
    }


    /**
     * 待审核配送员
     */
    public function applyUser()
    {
        $registerMod = M('Deliver_user_register');
        $condition = ['status' => 0];
        $get = $_GET;
        if ($get['searchtype'] && $get['keyword']) {
            $condition[$get['searchtype']] = ['LIKE', '%' . $get['keyword'] . '%'];
        }
        if ($get['begin_time']) {
            $condition['apply_time'][] = ['gt', strtotime($get['begin_time'])];
        }
        if ($get['end_time']) {
            $condition['apply_time'][] = ['lt', strtotime($get['end_time']) + 86400];
        }
        if ($get['province_idss']) {
            $condition['province_id'] = intval($get['province_idss']);
        }
        if ($get['city_idss']) {
            $condition['city_id'] = intval($get['city_idss']);
        }
        $order = 'apply_time DESC, id DESC';
        import('@.ORG.system_page');
        $count = $registerMod->where($condition)->count();
        $p = new Page($count, 20);
        $users = $registerMod
            ->where($condition)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order($order)
            ->select();
        $this->assign('users', $users);

        $provinceIds = array_column($users, 'province_id');
        $cityIds = array_column($users, 'city_id');
        $areaIds = array_filter(array_merge($provinceIds, $cityIds));
        if ($areaIds) {
            $areas = M('Area')->where(['area_id' => ['IN', $areaIds]])->getField('area_id,area_name');
        }
        $this->assign('areas', $areas ?? []);

        $show_merchant_user = 0;
        if($this->system_session['level']==2||in_array(838,$this->system_session['menus'])){
            $show_merchant_user = 1;
        }
        $this->assign('show_merchant_user',$show_merchant_user);
        
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function handleRegister()
    {
        if ($_POST) {
            $id = $_POST['id'] ?? 0;
            $status = $_POST['handle_status'] ?? 0;
            $reason = $_POST['handle_reason'] ?? '';
            $handleTime = time();
            $adminId = $this->system_session['id'] ?? 0;
            $register = M('Deliver_user_register')->where(['id' => $id])->find();
            if ($status == 1) {
                //通过
                if ($this->deliver_user->where(array('phone' => $register['phone'], 'status' => array('neq', 4)))->find()) {
                    $this->error('该手机号已经是配送员账号了，不能重复申请');
                }
            } else if ($status == 2) {
                //驳回
                if (empty($reason)) {
                    $this->error('驳回必须填写审核备注');
                }
            } else {
                $this->error('审核状态有误');
            }
            if ($status == 1) {
                $_POST['register_id'] = $id;
                $_POST['name'] = $register['truename'];
                $_POST['phone'] = $register['phone'];
                $_POST['card_number'] = $register['card_number'];
                $_POST['user_field'] = $register['user_field'];
                $_POST['register_type'] = 1;
                $_POST['user_type'] = 1;
                $_POST['face_recognition_images'] = $register['face_recognition_images'];
                $this->user_add($_POST);
            }
            M('Deliver_user_register')->where(['id' => $id])->save(['status' => $status, 'reason' => $reason, 'handle_time' => $handleTime, 'admin_id' => $adminId]);
			
			//发送短信给配送员
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $register['phone'];
			$sms_data['sendto'] = 'user';
			if ($status == 1) {
				$sms_data['content'] = '您申请配送员的审核结果为[通过]，初始密码为['.$_POST['pwd'].']！详情请联系客服。';
			}else if ($status == 2){
				$sms_data['content'] = '您申请配送员的审核结果为[驳回]，原因为['.$reason.']！详情请联系客服。';
			}
			Sms::sendSms($sms_data);
			
            $this->success('审核成功');
        } else {
            $id = I('get.id', 0, 'intval');
            $register = M('Deliver_user_register')->where(['id' => $id])->find();
            if ($register['city_id']) {
                $register['area_name'] = M('Area')->where(['area_id' => ['IN', [$register['province_id'], $register['city_id']]]])->order('area_id asc')->getField('group_concat(area_name)');
            }
            $cityId = $register['city_id']?:$this->config['now_city'];
            $deliverCustoms = $this->getDeliveryCustom($cityId);
            $this->assign('deliverCustoms',$deliverCustoms);
            $default_city = M('Area')->where(array('area_id'=>$cityId))->find();
            $this->assign('default_city', $default_city);
            $this->assign('traffic', $this->deliver_user->traffic);
            if (empty($register)) {
                $this->error('申请记录不存在');
            } else {
                $this->assign('register', $register);
                $this->display();
            }
        }
    }

    public function handleRegisterFields()
    {
        $id = I('get.id', 0, 'intval');
        $register = M('Deliver_user_register')->where(['id' => $id])->find();
        if($register['user_field']) $register['user_field'] = unserialize($register['user_field']);
        
        if (empty($register)) {
            $this->error('申请记录不存在');
        } else {
            $this->assign('register', $register);
            $this->display();
        }
    }

    /**
     * 清除配送员认证头像
     * @date: 2021/09/16
     */
    public function delFaceImg()
    {
        $uid = $_POST['uid'] ?? 0;
        try {
            if ($uid < 1) {
                throw new Exception('请选择配送员');
            }
            $thisUser = $this->deliver_user->where(['uid' => $uid, 'status' => ['NEQ', 4]])->find();
            if (empty($thisUser)) {
                throw new Exception('配送员不存在');
            }
            D('House_face_img')->deliver_baidu_face_del($uid);
            $this->susccess('删除成功');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }


    public function deliverClockRecords()
    {
        $uid = I('get.uid', 0, 'intval');
        $list = D('Deliver_user_clock_log')->where(['uid'=>$uid])->order('id DESC')->limit(200)->select();
        $this->assign('list', $list);
        $this->display(); 
    }

    //配送地图轨迹
    public function map(){
        $supply_id = I('supply_id',0,'intval');
        $uid =  I('uid',0,'intval');
        $supply = M('Deliver_supply')->where(['supply_id'=>$supply_id])->find();
        $user = M('Deliver_user')->where(['uid'=>$uid])->find();

        if (C('config.tianditu_web_key')) {
            list($supply['from_lnt'], $supply['from_lat']) = Coordinate::bd09ToWgs84($supply['from_lnt'], $supply['from_lat']);
            list($supply['aim_lnt'], $supply['aim_lat']) = Coordinate::bd09ToWgs84($supply['aim_lnt'], $supply['aim_lat']);
            list($user['now_lng'], $user['now_lat']) = Coordinate::bd09ToWgs84($user['now_lng'], $user['now_lat']);
        }

        $positions = [
            ['title' => $supply['from_site'], 'lng' => $supply['from_lnt'], 'lat' => $supply['from_lat']],
            ['title' => $supply['aim_site'], 'lng' => $supply['aim_lnt'], 'lat' => $supply['aim_lat']],
            ['title' => $user['name'], 'lng' => $user['now_lng'], 'lat' => $user['now_lat']],
        ];

        $toUserDistance = getRange(getDistance($user['now_lat'],$user['now_lng'],$supply['aim_lat'],$supply['aim_lnt']));
        $toMerDistance =  getRange(getDistance($user['now_lat'],$user['now_lng'],$supply['from_lat'],$supply['from_lnt']));

        $this->assign('positions', $positions);
        $this->assign('toUserDistance', $toUserDistance);
        $this->assign('toMerDistance', $toMerDistance);
        $this->display();
    }
}
?>