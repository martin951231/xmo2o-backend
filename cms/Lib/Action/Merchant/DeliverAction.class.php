<?php

/*
 * 用户中心
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
	/**
	 * 配送员列表
	 */
    public function user()
    {
        $condition_user['mer_id'] = $this->merchant_session['mer_id'];
        $condition_user['group'] = 2;
        $condition_user['status'] = array('neq',4);
        if ($this->merchant_session['store_id']) {
            $condition_user['_string'] = 'FIND_IN_SET(' . $this->merchant_session['store_id'] . ',' . '`store_ids`)';
        }
        $count_user = $this->deliver_user->where($condition_user)->count('distinct(`phone`)');
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 15);
        $user_list = $this->deliver_user->field(true)->where($condition_user)->group('phone')->order('`uid` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $staffSess = $this->staff_session;
        if (count($user_list) > 0) {
            foreach ($user_list as $k => $uinfo) {
                $store_ids = !empty($uinfo['store_ids']) ? explode(',', $uinfo['store_ids']) : [$uinfo['store_id']];
                $user_list[$k]['store_num'] = count($store_ids);
                $store_names = D(_view('merchant_store'))->where(array('store_id' => array('in', $store_ids)))->getField('name', true);
                $user_list[$k]['store_names'] = is_array($store_names)?implode($store_names,"<br/>"):'暂无店铺';

                //如果是店员中心，则重新统计配送总量，只统计当前店员所在店铺
                if($staffSess){
                    $user_list[$k]['num'] = D('Deliver_supply')->getDeliverCount($uinfo['uid'],$staffSess['store_id']);
                }
            }
        }

        $this->assign('user_list', $user_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display('lists');
    }

    public function deliverEdit()
    {
        $merId = $this->merchant_session['mer_id'];
        if (IS_POST) {
            $uid = intval($_POST['uid']);
            $deliverUserMod = D('Deliver_user');
            $deliver = $deliverUserMod->where(['uid' => $uid])->find();
            if (!$deliver) {
                $this->error(L_('配送员不存在'));
            }
            $column['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
            $column['phone'] = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
            $column['pwd'] = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
            if ($column['pwd']) {
                $column['pwd'] = md5($column['pwd']);
            } else {
                unset($column['pwd']);
            }
            $column['status'] = intval($_POST['status']);
            $column['last_time'] = $_SERVER['REQUEST_TIME'];
            $column['traffic'] = $_POST['traffic'];
            $_POST['phone_country_type'] && $column['phone_country_type'] = intval($_POST['phone_country_type']);
            $column['phone'] = phone_format($column['phone_country_type'], $column['phone']);

            if (empty($column['name'])) {
                $this->error(L_('姓名不能为空'));
            }
            if (empty($column['phone'])) {
                $this->error(L_('联系电话不能为空'));
            }

            if ($column['phone'] != $deliver['phone'] && $deliverUserMod->where(['phone' => $column['phone'], 'mer_id' => $merId, 'status' => ['NEQ',4]])->find()) {
                $this->error(L_('该手机号已经是配送员，请勿重复添加'));
            }
            $where = [
                'mer_id' => $merId,
                'group' => 2,
                'phone' => $deliver['phone'],
                'status' => array('neq',4)
            ];
            if (D('deliver_user')->where($where)->data($column)->save()) {
                $this->success(L_('修改成功！'));
            } else {
                $this->error(L_('修改失败！请检查内容是否有过修改（必须修改）后重试~'));
            }
        } else {
            $uid = $_GET['uid'];
            if (!$uid) {
                $this->error(L_('非法操作'));
            }
            $deliver = D('deliver_user')->where(array('uid' => $uid, 'mer_id' => $merId,'status'=>array('neq',4)))->find();
            if (!$deliver) {
                $this->error(L_('非法操作'));
            }
            $this->assign('now_user', $deliver);
            $this->assign('traffic', $this->deliver_user->traffic);
            $this->display();
        }
    }

    public function detail() {
        $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
        if ($uid < 1) {
            $this->error(L_('参数有误'));
        }
        $deliverUser = $this->deliver_user->find($uid);
        if (empty($deliverUser)) {
            $this->error(L_('配送员不存在'));
        }
        $where = [
            'mer_id'=>$this->merchant_session['mer_id'],
            'group'=>2,
            'phone'=>$deliverUser['phone']
        ];
        $count_user = $this->deliver_user->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 15);
        $user_list = $this->deliver_user->field(true)->where($where)->order('`uid` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $merstore['mer_id'] = $this->merchant_session['mer_id'];
        $merstore['have_shop'] = 1;
        $merstore['status'] = ['neq',4];
        $storeInfos = D(_view('merchant_store'))->where($merstore)->select();
        $storeIds = array_column($storeInfos,'store_id');
        $storeInfos = array_combine($storeIds,$storeInfos);
        $this->assign('storeInfoNew',$storeInfos);
        $this->assign('user_list', $user_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display('user');
    }
    
    /**
     * 配送员添加
     */
    public function user_add() {
    	$mer_id = $this->merchant_session['mer_id'];
    	if($_POST){
            $post = $_POST;
            $storeIds = isset($_POST['store_ids']) ? array_unique($_POST['store_ids']) : [];
            $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
            $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
            $pwd = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
            if (empty($storeIds)) {
                $this->error(L_('请选择店铺'));
            }
            if (empty($name)) {
                $this->error(L_('姓名不能为空'));
            }
            if (empty($phone)) {
                $this->error(L_('联系电话不能为空'));
            }
            if (!preg_match('/^\d+$/', $phone)) {
                $this->error(L_('联系电话格式不正确'));
            }
            if (empty($pwd)) {
                $this->error(L_('密码不能为空'));
            }

            //校验密码
            $check_password = verifyPassword($pwd);
            if($check_password['code']!=0){
                $this->error($check_password['msg']);
            }

            //循环插入多条记录
            $storeMod = D('Merchant_store');
            $firstStore = $storeIds[0];
            $store = $storeMod->where(['mer_id' => $mer_id, 'store_id' => $firstStore])->find();

            $deliver = [
                'name' => $name,
                'phone' => $phone,
                'pwd' => md5($pwd),
                'mer_id' => $mer_id,
                'store_id' => $firstStore,
                'store_ids' => implode($storeIds, ','),
                'city_id' => $store['city_id'],
                'province_id' => $store['province_id'],
                'circle_id' => $store['circle_id'],
                'area_id' => $store['area_id'],
                'site' => $store['adress'],
                'lng' => $store['long'],
                'lat' => $store['lat'],
                'create_time' => $_SERVER['REQUEST_TIME'],
                'status' => intval($post['status']),
                'last_time' => $_SERVER['REQUEST_TIME'],
                'group' => 2,
                'range' => intval($post['range']),
                'traffic' => $post['traffic'],
                'show_phone_status' => $post['show_phone_status'] ?: 0,
            ];
            if ($post['phone_country_type']) {
                $deliver['phone_country_type'] = intval($post['phone_country_type']);
                $deliver['phone'] = phone_format($deliver['phone_country_type'], $deliver['phone']);
            }
            $deliverUser = D('Deliver_user')->where(['phone' => $phone])->find();
            if ($deliverUser&&$deliverUser['status']!=4) {
                $group = $deliverUser['group'] == 1 ? '平台' : '商家';
                $this->error(L_('该手机号已经是' . $group . '的配送员，请勿重复添加'));
            }elseif($deliverUser&&$deliverUser['status']==4){
                $id = D('Deliver_user')->where(['uid'=>$deliverUser['uid']])->data($deliver)->save();
            }else{
                $id = D('Deliver_user')->add($deliver);
            }
            
            if (!$id) {
                $this->error(L_('保存失败，请重试'));
            }
            $this->success(L_('保存成功'));
    	}else{
    		// 该商家下的所有外卖店铺
    		$merstore['mer_id'] = $mer_id;
            $merstore['status'] = ['neq',4];
    		if ($this->merchant_session['store_id']) {
    		    $merstore['store_id'] = $this->merchant_session['store_id'];
    		}
    		$waimai_store = D(_view('merchant_store'))->where($merstore)->order('sort DESC')->select();
    		$this->assign('waimai_store',$waimai_store);
            $this->assign('traffic', $this->deliver_user->traffic);
    	}
    	
    	$this->display();
    }
    
    /**
     * 配送员修改
     */
    public function user_edit() {
    	$mer_id = $this->merchant_session['mer_id'];
    	if($_POST){
            $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
    		$column['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
    		$column['phone'] = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    		$column['pwd'] = isset($_POST['pwd']) ? htmlspecialchars($_POST['pwd']) : '';
            $storeIds = isset($_POST['store_ids']) ? array_unique($_POST['store_ids']) : [];
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
            if (empty($storeIds)) {
                $this->error(L_('请选择店铺'));
            }
            if (empty($column['name'])) {
                $this->error(L_('姓名不能为空'));
            }
            if (empty($column['phone'])) {
                $this->error(L_('联系电话不能为空'));
            }
            if (!preg_match('/^\d+$/', $column['phone'])) {
                $this->error(L_('联系电话格式不正确'));
            }

            $storeMod = D('Merchant_store');
            $selectedStores = $storeMod->where(['mer_id' => $mer_id, 'store_id' => ['IN', $storeIds]])->select();
            if (count($selectedStores) != count($storeIds)) {
                $this->error(L_('店铺不存在'));
            }
            $store = $selectedStores[0];
            $column['store_id'] = $store['store_id'];
            $column['store_ids'] = implode($storeIds, ',');
            $column['city_id'] = $store['city_id'];
            $column['province_id'] = $store['province_id'];
            $column['circle_id'] = $store['circle_id'];
            $column['area_id'] = $store['area_id'];
            $column['site'] = $store['adress'];
            $column['lng'] = $store['long'];
            $column['lat'] = $store['lat'];
            $column['status'] = intval($_POST['status']);
            $column['range'] = intval($_POST['range']);
            $column['show_phone_status'] = intval($_POST['show_phone_status']);
            $column['last_time'] = $_SERVER['REQUEST_TIME'];
            $column['traffic'] = $_POST['traffic'];
            $_POST['phone_country_type'] && $column['phone_country_type'] = intval($_POST['phone_country_type']);
            $column['phone'] = phone_format($column['phone_country_type'], $column['phone']);


            $user = D('Deliver_user')->field(true)->where(['phone' => $column['phone'], 'uid' => ['NEQ', $uid], 'status' => ['NEQ',4]])->find();
            if ($user) {
                $this->error(L_('该手机号已经是商家的配送员，请勿重复添加'));
            }
    		
    		if(D('deliver_user')->where(array('uid'=>$uid,'status'=>array('neq',4)))->data($column)->save()){
    			$this->success(L_('修改成功！'));
    		}else{
    			$this->error(L_('修改失败！请检查内容是否有过修改（必须修改）后重试~'));
    		}
    	}else{
    		$uid = $_GET['uid'];
    		if(!$uid){
    			$this->error(L_('非法操作'));
    		}
    		$deliver = D('deliver_user')->where(array('uid'=>$uid,'mer_id'=>$mer_id,'status'=>array('neq',4)))->find();
    		if(!$deliver){
    			$this->error(L_('非法操作'));
    		}
    		$this->assign('now_user',$deliver);
    		
    		$merstore['mer_id'] = $mer_id;
            $merstore['status'] = ['neq',4];
            $staffStoreId = $this->merchant_session['store_id'];

            $storeIds = !empty($deliver['store_ids']) ? explode(',', $deliver['store_ids']) : [$deliver['store_id']];
            $waimai_store = D(_view('merchant_store'))->where($merstore)->order('sort DESC')->select();
            foreach ($waimai_store as $k => $v) {
                $waimai_store[$k]['checked'] = in_array($v['store_id'], $storeIds) ? true : false;
                if ($staffStoreId) {
                    $waimai_store[$k]['disabled'] = $v['store_id'] == $staffStoreId ? false : true;
                } else {
                    $waimai_store[$k]['disabled'] = false;
                }
            }
    		$this->assign('waimai_store',$waimai_store);
            $this->assign('traffic', $this->deliver_user->traffic);
    	}
    	$this->display();
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
            $mer_id = $this->merchant_session['mer_id'];
            $condition_user['mer_id'] = $mer_id;
            $condition_user['uid'] = intval($_POST['uid']);
            if ($this->merchant_session['store_id']) {
                $condition_user['store_id'] = $this->merchant_session['store_id'];
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
     * 删除商家配送员
     */
    public function user_del(){
    	$uid = $_POST['uid'];
    	if(!$uid){
    		$this->error(L_('非法操作'));
    	}
    	$mer_id = $this->merchant_session['mer_id'];
    	$condition_user['mer_id'] = $mer_id;
    	$condition_user['uid'] = $uid;
		if ($this->merchant_session['store_id']) {
		    $condition_user['store_id'] = $this->merchant_session['store_id'];
		}
    	$count_user = $this->deliver_user->where($condition_user)->find();
    	if(!$count_user){
    		$this->error(L_('非法操作'));
    	}
    	
    	$result = $this->deliver_user->where($condition_user)->save(array('status'=>4));
    	if(!$result){
    		$this->error(L_('删除失败，请稍后重试'));
    	}
    	$this->success(L_('操作成功'));
    }

    //配送列表
    public  function deliverList()
    {
        $selectStoreId = I("selectStoreId", 0, "intval");
        $selectUserId = I("selectUserId", 0, "intval");
        $phone = I("phone", 0);
        $orderNum = I("orderNum", 0);
        $isExport = I('is_export',0);

        $mer_id = $this->merchant_session['mer_id'];
        //获取商家店铺ID
        $where = "mer_id={$mer_id} AND status=1 AND (have_meal=1 OR have_shop=1)";
        if ($this->merchant_session['store_id']) {
            $where .= " AND store_id=" . $this->merchant_session['store_id'];
            $selectStoreId = $this->merchant_session['store_id'];
        }
        $stores = D("Merchant_store")->field(true)->where($where)->select();
        $storeIds = array();
        foreach ($stores as $val) {
            $storeIds[] = $val['store_id'];
        }

        if ($selectStoreId && !in_array($selectStoreId, $storeIds)) {
            $this->error(L_("参数出错"));
        }
        //获取商家的所有配送员
        $userWhere = array('mer_id' => $mer_id);
        if ($this->merchant_session['store_id']) {
            $userWhere['store_id'] = $this->merchant_session['store_id'];
        }
        $delivers = D("Deliver_user")->field(true)->where($userWhere)->order('status DESC')->select();
        foreach ($delivers as $key => $val) {
            if ($val['status'] == 0) {
                $delivers[$key]['name'] = $val['name']." (已禁用)";
            }
        }

        $sql = "SELECT s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.create_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash FROM " . C('DB_PREFIX') . "merchant_store AS m INNER JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";
        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m INNER JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";
        
        $sql .= ' WHERE s.type=1 AND s.mer_id=' . $this->merchant_session['mer_id'];
        $sql_count .= ' WHERE s.type=1 AND s.mer_id=' . $this->merchant_session['mer_id'];
        if ($phone) {
            $sql .= " AND s.phone=".$phone;
            $sql_count .= " AND s.phone=".$phone;
        }


        if ($selectStoreId) {
            $sql .= " AND s.store_id=".$selectStoreId;
            $sql_count .= " AND s.store_id=".$selectStoreId;
        }
        
        if ($selectUserId) {
            $sql .= "  AND s.uid=".$selectUserId;
            $sql_count .= "  AND s.uid=".$selectUserId;
        }
        
        $res_count = D()->query($sql_count);
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;

        import('@.ORG.merchant_page');
        $p = new Page($count_order, 20);
        
        if($isExport){
            $sql .= ' ORDER BY s.`supply_id` DESC';
        }else{
            $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        }
        $supply_info = D()->query($sql);

        foreach ($supply_info as &$value) {
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['start_time'] = $value['start_time'] ? date("Y-m-d H:i:s", $value['start_time']) : '-';
			$value['end_time'] = $value['end_time'] ? date("Y-m-d H:i:s", $value['end_time']) : '-';
            $value['paid'] = $value['paid'] == 1 ? L_("已支付") : L_("未支付");
            $value['pay_type'] = $value['pay_type'] == "offline" ? L_("线下支付") : L_("线上支付");
            //订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            //配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            switch ($value['status']) {
                case 1:
                    $value['order_status'] = '<font color="red">等待接单</font>';
                    break;
                case 2:
                    $value['order_status'] = L_("已接单");
                    break;
                case 3:
                    $value['order_status'] = L_("已取货");
                    break;
                case 4:
                    $value['order_status'] = L_("开始配送");
                    break;
                case 5:
                    $value['order_status'] = L_("配送完成");
                    break;
                case 6:
                    $value['order_status'] = L_("已评价");
                    break;
//                 case 6:
//                     $value['order_status'] = "已退单";
//                     break;
//                 case 7:
//                     $value['order_status'] = "已取消";
//                     break;
//                 case 68:
//                     $value['order_status'] = "已接单";
                default:
                    $value['order_status'] = L_("订单失效");
                    break;
            }
            $value['order_status_desc'] = strip_tags($value['order_status']);
        }

        //导出
        if($isExport){
            $config = [
                'header' => [
                    ['title' => L_('店铺名称'), 'value_key' => 'storename'],
                    ['title' => L_('客户昵称'), 'value_key' => 'username'],
                    ['title' => L_('客户手机号'), 'value_key' => 'userphone'],
                    ['title' => L_('客户地址'), 'value_key' => 'aim_site'],
                    ['title' => L_('订单价格'), 'value_key' => 'money'],
                    ['title' => L_('应收现金'), 'value_key' => 'deliver_cash'],
                    ['title' => L_('支付状态'), 'value_key' => 'paid'],
                    ['title' => L_('配送员昵称'), 'value_key' => 'name'],
                    ['title' => L_('配送员手机号'), 'value_key' => 'phone'],
                    ['title' => L_('开始时间'), 'value_key' => 'start_time'],
                    ['title' => L_('结束时间'), 'value_key' => 'end_time'],
                    ['title' => L_('创建时间'), 'value_key' => 'create_time'],
                    ['title' => L_('配送状态'), 'value_key' => 'order_status_desc'],
                ],
                'body' => $supply_info,
                'file_name' => time(),
                'sheet_title' => '配送列表'
            ];
            R('System/Shop/download_store_list', ['config' => $config]);
            exit;
        }


        $this->assign('selectStoreId', $selectStoreId);
        $this->assign('phone', $phone);
        $this->assign('orderNum', $orderNum);
        $this->assign('selectUserId', $selectUserId);
        $this->assign('stores', $stores);
        $this->assign('delivers', $delivers);
        $this->assign('pagebar', $p->show());
        $this->assign('supply_info', $supply_info);

        $this->display();
    }
    
    public function count_log()
    {
    	$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    	$condition_user = array('mer_id' => $this->merchant_session['mer_id'], 'uid' => $uid);
        $user = $this->deliver_user->field(true)->where($condition_user)->find();
        if (empty($user)) $this->error(L_('不存在的配送员'));
        $deliver_count_obj = D('Deliver_count');
        $count = $deliver_count_obj->field(true)->where(array('uid' => $uid))->order('`id` DESC')->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $count_list = $deliver_count_obj->field(true)->where(array('uid' => $uid))->order('`id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $staffSess = $this->staff_session;
        foreach ($count_list as &$row) {
        	$row['today'] = date('Y-m-d', strtotime($row['today'] . '000000'));
            //如果是店员中心，则重新统计配送总量，只统计当前店员所在店铺
            if($staffSess){
                $row['num'] = D('Deliver_supply')->getDeliverCount($uid,$staffSess['store_id'],['end_time'=>[['GT',strtotime($row['today'])],['LT',strtotime($row['today'])+86400]]]);
            }
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
		$begin_time = isset($_POST['begin_time']) ? htmlspecialchars($_POST['begin_time']) : '';
		$end_time = isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : '';
        $store_id = !empty($_POST['store_id']) ? intval($_POST['store_id']) : intval($this->merchant_session['store_id']);
    	$condition_user = array('mer_id' => $this->merchant_session['mer_id'], 'uid' => $uid);
        $user = $this->deliver_user->field(true)->where($condition_user)->find();
        if (empty($user)) $this->error(L_('不存在的配送员'));
        
        
        $sql = "SELECT s.`supply_id`, s.order_id, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, s.create_time, s.start_time, s.end_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash,s.virtual_phone,s.virtual_phone_overtime FROM " . C('DB_PREFIX') . "merchant_store AS m INNER JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
        $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant_store AS m INNER JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id";
        
        $sql .= ' WHERE s.type=1 AND s.mer_id=' . $this->merchant_session['mer_id'] . ' AND s.uid=' . $uid;
        $sql_count .= ' WHERE s.type=1 AND s.mer_id=' . $this->merchant_session['mer_id'] . ' AND s.uid=' . $uid;
        
		if ($begin_time && $end_time) {
			$sql .= ' AND s.start_time>' . strtotime($begin_time) . ' AND s.start_time<' . strtotime($end_time);
			$sql_count .= ' AND s.start_time>' . strtotime($begin_time) . ' AND s.start_time<' . strtotime($end_time);
		}
        if ($store_id > 0) {
            $sql .= ' AND s.store_id = ' . $store_id;
            $sql_count .= ' AND s.store_id = ' . $store_id;
        }
        $res_count = D()->query($sql_count);
        $count_order = isset($res_count[0]['count']) ? $res_count[0]['count'] : 0;

        
        import('@.ORG.merchant_page');
        $p = new Page($count_order, 20);
        $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $supply_info = D()->query($sql);
        foreach ($supply_info as &$value) {
            $value['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
			$value['start_time'] = $value['start_time'] ? date("Y-m-d H:i:s", $value['start_time']) : '-';
			$value['end_time'] = $value['end_time'] ? date("Y-m-d H:i:s", $value['end_time']) : '-';
            $value['paid'] = $value['paid'] == 1 ? L_("已支付") : L_("未支付");
            $value['pay_type'] = $value['pay_type'] == "offline" ? L_("线下支付") : L_("线上支付");
            
            // 虚拟手机号
            if ($value['virtual_phone']) {
                $virtual_phone = explode(';', $value['virtual_phone']);
                $virtual_phone_arr = array();
                foreach ($virtual_phone as $_vPhone) {
                    $virtual_phone_arr[] = explode(',', $_vPhone);
                }
                $value['virtual_phone_arr'] = $virtual_phone_arr;
            }

            //订单状态（0：订单失效，1:订单完成，2：商家未确认，3：商家已确认，4已取餐，5：正在配送，6：退单,7商家取消订单,8配送员已接单）
            //配送状态(0失败 1等待接单 2接单 3取货 4开始配送 5完成）
            switch ($value['status']) {
                case 1:
                    $value['order_status'] = '<font color="red">等待接单</font>';
                    break;
                case 2:
                    $value['order_status'] = L_("已接单");
                    break;
                case 3:
                    $value['order_status'] = L_("已取货");
                    break;
                case 4:
                    $value['order_status'] = L_("开始配送");
                    break;
                case 5:
                    $value['order_status'] = L_("配送完成");
                    break;
                case 6:
                    $value['order_status'] = L_("已评价");
                    break;
                default:
                    $value['order_status'] = L_("订单失效");
                    break;
            }
        }

        //商家所有店铺
        $merstore['mer_id'] = $this->merchant_session['mer_id'];
        $merstore['have_shop'] = 1;
        $merstore['status'] = ['neq',4];
        if ($this->merchant_session['store_id']) {
            $merstore['store_id'] = $this->merchant_session['store_id'];
        }
        $waimai_store = D(_view('merchant_store'))->where($merstore)->order('sort DESC')->select();
        $this->assign('waimai_store',$waimai_store);

        $this->assign('supply_info', $supply_info);
        $this->assign('pagebar', $p->show());
        $this->assign('user', $user);
        $this->assign(array('begin_time' => $begin_time, 'end_time' => $end_time, 'store_id' => $store_id));
        $this->display();
    }
}
