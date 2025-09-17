<?php
/*
 * 付款管理
 *
 */
class CompanypayAction extends BaseAction{
	public function index(){
		if($this->config['company_pay_open']==0){   redirect(U('Companypay/withdraw_by_hand'));die;}
		if (!empty($_GET['keyword'])) {
			if ($_GET['searchtype'] == 'pay_id') {
				$condition['c.pay_id'] = $_GET['keyword'];
			} else if ($_GET['searchtype'] == 'phone') {
				$condition['c.phone'] = $_GET['keyword'] ;
			} else if($_GET['searchtype'] == 'user'){
				$condition['c.pay_id'] = $_GET['keyword'] ;
				$condition['c.pay_type'] = 'user' ;
			} else if($_GET['searchtype'] == 'house'){
				$condition['c.pay_id'] = $_GET['keyword'] ;
				$condition['c.pay_type'] = 'house' ;
			}
		}
        if($_GET['pay_type']){
            $condition['c.pay_type']=$_GET['pay_type'];
        }
		if($_GET['searchstatus']>-1){
			$condition['c.status']=$_GET['searchstatus'];
		}else{
			$_GET['searchstatus']=-1;
		}
		$order_string = 'c.`pigcms_id` DESC';
		if($_GET['sort']){
			switch($_GET['sort']){
				case 'uid':
					$order_string = 'c.`uid` DESC';
					break;
				case 'lastTime':
					$order_string = 'c.`last_time` DESC';
					break;
				case 'money':
					$order_string = 'c.`now_money` DESC';
					break;
				case 'score':
					$order_string = 'c.`score_count` DESC';
					break;
			}
		}

		if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$time_condition = " (c.".$_GET['timesearchtype']." BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition['_string']=$time_condition;
			$this->assign('begin_time',$_GET['begin_time']);
			$this->assign('end_time',$_GET['end_time']);
		}

		$pay = M('Companypay');
		$join = ' as c left join '.C('DB_PREFIX').'admin as a on c.admin_id = a.id';
		$count_pay = $pay->where($condition)->count();
		$pay_type = array(
				'merchant'=>'商家',
				'user'=>'用户',
				'house'=>'小区',
				'withdraw'=>'商家提现',
		);
        $this->assign('pay_types',$pay_type);
		$status = array('未付款','已付款', '已拒绝');
		if($_GET['export']==1){
			set_time_limit(0);
			require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
			$title = '导出付款数据';
			if($_GET['begin_time']&&$_GET['end_time']){
				$title.='('.$_GET['begin_time'].'至'.$_GET['end_time'].')';
			}
			$objExcel = new PHPExcel();
			$objProps = $objExcel->getProperties();
			// 设置文档基本属性
			$objProps->setCreator($title);
			$objProps->setTitle($title);
			$objProps->setSubject($title);
			$objProps->setDescription($title);
			$pay_list = $pay->field('c.pigcms_id,c.pay_type,c.pay_id,c.openid,c.nickname,c.phone,c.money,c.desc,c.status,c.add_time,c.pay_time,c.trade_no,c.payment_no,c.result,a.realname')->where($condition)->join($join)->order($order_string)->select();

			// 设置当前的sheet
			$length = ceil(count($pay_list) / 1000);
			for ($i = 0; $i < $length; $i++) {
				$i && $objExcel->createSheet();
				$objExcel->setActiveSheetIndex($i);

				$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千个提现信息');
				$objActSheet = $objExcel->getActiveSheet();

				$objActSheet->setCellValue('A1', '编号');
				$objActSheet->setCellValue('B1', '付款类型');
				$objActSheet->setCellValue('C1', '商家/用户/小区ID');
				$objActSheet->setCellValue('D1', '联系电话');
				$objActSheet->setCellValue('E1', '金额');
				$objActSheet->setCellValue('F1', '描述');
				$objActSheet->setCellValue('G1', '申请时间');
				$objActSheet->setCellValue('H1', '付款人');
				$objActSheet->setCellValue('I1', '付款时间');
				$objActSheet->setCellValue('J1', '状态');

				if (!empty($pay_list)) {
					$index = 2;
					foreach ($pay_list as $value) {
						$objActSheet->setCellValueExplicit('A' . $index, $value['pigcms_id']);
						$objActSheet->setCellValueExplicit('B' . $index, $pay_type[$value['pay_type']]);
						$objActSheet->setCellValueExplicit('C' . $index, $value['pay_id'] );
						$objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
						$objActSheet->setCellValueExplicit('E' . $index, floatval($value['money']/100));
						$objActSheet->setCellValueExplicit('F' . $index, $value['desc']. '');
						$objActSheet->setCellValueExplicit('G' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
						$objActSheet->setCellValueExplicit('H' . $index, $value['realname'] ? $value['realname'] : '--');
						$objActSheet->setCellValueExplicit('I' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '无');
						$objActSheet->setCellValueExplicit('J' . $index,$status[$value['status']]. '');
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
			header('Content-Disposition:attachment;filename="'.$title.'_' . date("Ymd h:i:s", time()) . '.xls"');
			header("Content-Transfer-Encoding:binary");
			$objWriter->save('php://output');
			exit();
		}
		import('@.ORG.system_page');
		$p = new Page($count_pay, 25);
		$pay_list = $pay->field('c.pigcms_id,c.pay_type,c.pay_id,c.openid,c.nickname,c.phone,c.money,c.desc,c.status,c.add_time,c.pay_time,c.trade_no,c.payment_no,c.result,a.realname')->join($join)->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

        foreach ($pay_list as &$value) {
            $value['user_type'] = $pay_type[$value['pay_type']] ?? '';
        }
		$this->assign('pay_list',$pay_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}
	//将已取消的订单恢复到未支付状态
	public function restore(){
		$res = M('Companypay')->where(array('pigcms_id'=>$_GET['pigcms_id']))->find();
		foreach ($res as $key=>$v) {
			$data[$key] = $v;
		}

		if($res['pay_type']=='merchant'){
			D('Merchant_money_list')->withdraw_status_notice($res['pigcms_id']);
		}else if($res['pay_type']=='user'){
			D('Merchant_money_list')->withdraw_status_notice($res['pigcms_id'],1,true);
		}
		$data['status']=$_GET['status'];
		if(M('Companypay')->where(array('pigcms_id'=>$_GET['pigcms_id']))->delete() && M('Companypay')->add($data)){
			$this->success("操作成功！");
		}else{
			$this->success('操作失败！请联系管理员！');
		}
	}
	public function api(){
		if(empty($this->config['company_pay_open'])){
			echo json_encode(array('err_code'=>1003,'err_msg'=>'网站未开启软件付款功能'));
			exit();
		}
		if($_POST['webKey'] != $this->config['company_pay_encrypt']){
			echo json_encode(array('err_code'=>1001,'err_msg'=>'通信密钥错误，请重新填写'));
			exit();
		}
		if($_POST['action'] == 'saveOrder'){
			if($_POST['status'] == 'ok'){
				$condition_companypay = array(
					'pigcms_id'	 => $_POST['pigcms_id'],
				);
				$data_companypay = array(
					'trade_no' 	 => $_POST['trade_no'],
					'payment_no' => $_POST['payment_no'],
					'status'	 => '1',
					'pay_time'	 => strtotime($_POST['payment_time'])
				);
				if(D('Companypay')->where($condition_companypay)->data($data_companypay)->save()){
					echo json_encode(array('err_code'=>0,'err_msg'=>'订单保存成功'));
				}else{
					echo json_encode(array('err_code'=>1002,'err_msg'=>'订单保存失败，请重试'));
				}
				exit();
			}else if($_POST['status'] == 'del'){
				$condition_companypay = array(
					'pigcms_id'	 => $_POST['pigcms_id'],
				);
				$data_companypay = array(
					'status'	 => '2'
				);
				if(D('Companypay')->where($condition_companypay)->data($data_companypay)->save()){
					echo json_encode(array('err_code'=>0,'err_msg'=>'订单保存成功'));
				}else{
					echo json_encode(array('err_code'=>1002,'err_msg'=>'订单保存失败，请重试'));
				}
				exit();
			}
		}else{
			// if(C('config.company_pay_house_oneDayPay')){ 
			// 	$village = D('House_village');
			// 	$village_info = $village->field('village_id,village_name,property_phone,last_bill_time')->select();
			// 	$village_pay = D('House_village_pay_order');
			// 	$companypay  = D('Companypay');
			// 	$add_time = time();
			// 	foreach($village_info as $v){
			// 		$sql_sum_money = "SELECT SUM(money) AS money FROM ".C('DB_PREFIX')."house_village_pay_order WHERE paid=1 AND is_pay_bill=0 AND pay_time>=".$v['last_bill_time']." AND village_id =".$v['village_id'];
			// 		$money = $village_pay->query($sql_sum_money);
			// 		$res= $village_pay->where("paid=1 AND is_pay_bill=0 AND pay_time>".$v['last_bill_time'])->setField('is_pay_bill',1);
			// 		if(!empty($money[0]['money'])){
			// 			$Values.="('house','".$v['village_id']."','".$v['property_phone']."','".($money[0]['money']*100)."','小区".$v['village_name']."订单对账|时间(".date('Y-m-d',$v['last_bill_time'])."~".date('Y-m-d').")|转账 ".$money[0]['money'].C('config.Currency_txt')."',0,".$add_time." ),";
			// 		}
			// 		$ids[]=$v['village_id'];
			// 	}
			// 	$sql_add_companypay = "INSERT INTO ".C('DB_PREFIX')."companypay (`pay_type`,`pay_id`,`phone`,`money`,`desc`,`status`,`add_time`) VALUES ".substr($Values,0,-1);
			// 	if(!$companypay->query($sql_add_companypay)){
			// 		if(!empty($ids)){
			// 			$where['village_id']=array('in',implode(',',$ids));
			// 			$village->where($where)->setField('last_bill_time',$add_time);
			// 		}	
			// 	}
				
			// }
			
			// $condition_companypay['status'] = '0';
			// if($_POST['webLastId']){
			// 	$condition_companypay['pigcms_id'] = array('gt',$_POST['webLastId']);
			// }
			
			// $payList = D('Companypay')->where($condition_companypay)->order('`pigcms_id` ASC')->limit(10)->select();
			// $returnList = array();
			// foreach($payList as $value){
			// 	$returnList[] = array(
			// 		'pigcms_id'	=>	$value['pigcms_id'],
			// 		'pay_type'	=>	$value['pay_type'],
			// 		'alias_type'=>	$this->getType($value['pay_type']),
			// 		'pay_id'	=>	$value['pay_id'],
			// 		'openid'	=>	$value['openid'],
			// 		'nickname'	=>	$value['nickname'],
			// 		'money'		=>	$value['money'],
			// 		'desc'		=>	$value['desc'],
			// 		'add_time'	=>	date('Y-m-d H:i:s',$value['add_time']),
			// 		'status'	=>	$value['status'],
			// 	);
			// }
			// echo json_encode(array('err_code'=>0,'result'=>$returnList,'count'=>count($returnList)));
			// exit();
		}
	}
	public function getType($pay_type){
		switch($pay_type){
			case 'merchant':
				return '商家';
			case 'user':
				return '用户';
			case 'house':
				return '小区';
			case 'village':
				return '小区';
		}
	}

	public function withdraw_by_hand(){
		if (!empty($_GET['keyword'])) {
			if ($_GET['searchtype'] == 'pay_id') {
				$condition['pay_id'] = $_GET['keyword'];
			} else if ($_GET['searchtype'] == 'phone') {
				$condition['phone'] = $_GET['keyword'] ;
			} else if($_GET['searchtype'] == 'user'){
				$condition['pay_id'] = $_GET['keyword'] ;

			}else if($_GET['searchtype'] == 'truename'){
				$condition['pay_id'] = array('like','%'.$_GET['keyword'].'%') ;

			}

		}
		$condition['pay_type'] = array('neq',2);
		$condition['withdraw_id'] = 0;
		if($_GET['searchstatus']>-1){
			$condition['status']=$_GET['searchstatus'];
		}else{
			$_GET['searchstatus']=-1;
		}
		$order_string = '`id` DESC';


		if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$time_condition = " (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition['_string']=$time_condition;
			$this->assign('begin_time',$_GET['begin_time']);
			$this->assign('end_time',$_GET['end_time']);
		}

		$pay = M('Withdraw_list');
		$count_pay = $pay->where($condition)->count();

		$pay_type = array(
				'0'=>'银行',
				'1'=>'支付宝',
		);
		$type = array(
				'user'=>'用户',
				'mer'=>'商家',
				'village'=>'小区',
		);
		$status = array('未支付','已支付', '已取消');
		if($_GET['export']==1){
			set_time_limit(0);
			require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
			$title = '导出付款数据';
			if($_GET['begin_time']&&$_GET['end_time']){
				$title.='('.$_GET['begin_time'].'至'.$_GET['end_time'].')';
			}
			$objExcel = new PHPExcel();
			$objProps = $objExcel->getProperties();
			// 设置文档基本属性
			$objProps->setCreator($title);
			$objProps->setTitle($title);
			$objProps->setSubject($title);
			$objProps->setDescription($title);
			$pay_list = $pay->field(true)->where($condition)->order($order_string)->select();

			// 设置当前的sheet
			$length = ceil(count($pay_list) / 1000);
			for ($i = 0; $i < $length; $i++) {
				$i && $objExcel->createSheet();
				$objExcel->setActiveSheetIndex($i);

				$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千个提现信息');
				$objActSheet = $objExcel->getActiveSheet();

				$objActSheet->setCellValue('A1', '编号');
				$objActSheet->setCellValue('B1', '付款类型');
				$objActSheet->setCellValue('C1', '商家/用户/小区ID');
				$objActSheet->setCellValue('D1', '联系电话');
				$objActSheet->setCellValue('E1', '账号');
				$objActSheet->setCellValue('F1', '账户信息');
				$objActSheet->setCellValue('G1', '金额');
				$objActSheet->setCellValue('H1', '描述');
				$objActSheet->setCellValue('I1', '添加时间');
				$objActSheet->setCellValue('J1', '支付时间');
				$objActSheet->setCellValue('K1', '状态');

				if (!empty($pay_list)) {
					$index = 2;
					foreach ($pay_list as $value) {
						$objActSheet->setCellValueExplicit('A' . $index, $value['pigcms_id']);
						$objActSheet->setCellValueExplicit('B' . $index, $pay_type[$value['pay_type']]);
						$objActSheet->setCellValueExplicit('C' . $index, $value['pay_id'] );
						$objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['account']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['remark']);
						$objActSheet->setCellValueExplicit('G' . $index, floatval($value['money']/100));
						$objActSheet->setCellValueExplicit('H' . $index, $value['desc']. '');
						$objActSheet->setCellValueExplicit('I' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
						$objActSheet->setCellValueExplicit('J' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '无');
						$objActSheet->setCellValueExplicit('K' . $index,$status[$value['status']]. '');
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
			header('Content-Disposition:attachment;filename="'.$title.'_' . date("Ymd h:i:s", time()) . '.xls"');
			header("Content-Transfer-Encoding:binary");
			$objWriter->save('php://output');
			exit();
		}
		import('@.ORG.system_page');
		$p = new Page($count_pay, 25);
		$pay_list = $pay->field(true)->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		$this->assign('pay_list',$pay_list);
		$this->assign('pay_type',$pay_type);
		$this->assign('type',$type);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}


	public function restore_withdraw(){
		$data['status']=$_GET['status'];
		if(D('Withdraw_list')->where(array('id'=>$_GET['id']))->save($data)){
			$withdraw = M('Withdraw_list')->where(array(array('id'=>$_GET['id'])))->find();
			if($withdraw['type']=='user'){
                if ($withdraw['deliver_withdraw_id'] > 0) {
                    $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
                    $params = [
                        'withdrawId' => $withdraw['deliver_withdraw_id'],
                        'status' => $data['status'],
                        'params' => ['admin_id' => $this->system_session['id']]
                    ];
                    invoke_v20_service($model, $params);
                } else {
                    D('Merchant_money_list')->withdraw_status_notice($withdraw['id'], 1);
                }

                if($data['status'] == 2){// 驳回审核 增加余额
                	$money = $withdraw['old_money']/100 ;
                	D('User')->add_money($withdraw['pay_id'], $money,'驳回提现 增加余额');
                }
			}
			if($withdraw['withdraw_id']>0){
				M('Merhcant_withdraw')->where(array('id'=>$withdraw['withdraw_id']))->setField('status',1);
			}
            $this->success("操作成功！", U('Companypay/withdraw_by_hand'));
		}else{
            $this->error('操作失败！请联系管理员！', U('Companypay/withdraw_by_hand'));
		}
	}

    /*
     * 用户批量提现
     * */
	public function withdraw_approval(){
		if($this->config['company_pay_open']==0){   redirect(U('Companypay/withdraw_by_hand'));die;}
		if (!empty($_GET['keyword'])) {
			if ($_GET['searchtype'] == 'phone') {
				$condition['c.phone'] = ['like','%'.$_GET['keyword'].'%'] ;
			} else if ($_GET['searchtype'] == 'ID') {
				$condition['a.user_id_number'] = ['like','%'.$_GET['keyword'].'%'] ;
			}
		}

        $condition['c.pay_type'] = 'user';
        $condition['c.status'] = ['IN',['0','10','20']];
		$order_string = '`pigcms_id` DESC';
		
		if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$time_condition = " (c.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition['_string']=$time_condition;

			$this->assign('begin_time',$_GET['begin_time']);
			$this->assign('end_time',$_GET['end_time']);
		}
		$pay = M('Companypay');
		$join = ' as c left join '.C('DB_PREFIX').'user as u on c.pay_id=u.uid left join '.C('DB_PREFIX').'user_authentication as a on a.uid=u.uid left join '.C('DB_PREFIX').'distributor as d on u.uid=d.uid and d.status=1';
		$field = 'c.*,a.user_id_number,d.city_id,d.project_id,u.uid';
		$count_pay = $pay->join($join)->where($condition)->count();
		import('@.ORG.system_page');
		$p = new Page($count_pay, 25);
		$pay_list = $pay->field($field)->join($join)->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		//分销员要显示所属城市和所属项目
        if(C('config.open_user_distributor')){
            $project_ids = array_column($pay_list, 'project_id');
            $city_ids = array_column($pay_list, 'city_id');
            $projectList = array_column(M('Appoint_project')->where(array('id'=>['in',$project_ids]))->select(),'project_name','id');
            $cityList = array_column(M('Area')->where(array('area_id'=>['in',$city_ids]))->select(),'area_name','area_id');
        }
        $pay_type = array(
            'merchant'=>'商家',
            'user'=>'用户',
            'house'=>'小区',
            'withdraw'=>'商家提现',
        );
        foreach ($pay_list as &$value){
            $value['user_type'] = $pay_type[$value['pay_type']] ?? '';
            $value['project_name'] = $projectList[$value['project_id']];
            $value['city_name'] = $cityList[$value['city_id']];
            $value['money'] = $value['money']/100;
            $value['status_txt'] = D('Merchant_money_list')->getStatusTxt($value['status']);
            $value['status_color'] = $value['status'] == 20 ? '#f00' : '#00aad1';
            //超过1小时还在提现中的提供手动查询按钮
            $value['can_get_apply_status'] = C('customization.withdraw_apply') ? ($value['apply_withdraw_time'] + 3600 < time() ? 1 : 0): 0;
            //获取失败原因
            $value['fail_reason'] = C('customization.withdraw_apply') ? D('Merchant_money_list')->getFailReasonTxt($value['fail_reason']) : '';
        }
		$this->assign('pay_list',$pay_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);

		// 查找所有待付款金额
		$count_pay_money = $pay->field('sum(c.money) as total_money')->join($join)->where($condition)->find();
		$this->assign('total_money', $count_pay_money['total_money']/100);
		$this->assign('withdraw_apply', C('customization.withdraw_apply'));
		$this->display();
	}

    /*
     * 同意提现
     * */
    public function agree_withdraw_approval(){
        set_time_limit(0);
        if(empty($_POST['pigcms_id'])){
            $this->error('缺少参数');
        }

        if(!is_array($_POST['pigcms_id'])){
            $_POST['pigcms_id'] = [$_POST['pigcms_id']];
        }
        $where = [
            'c.pigcms_id' => ['in',$_POST['pigcms_id']]
        ];
        $join = ' as c left join '.C('DB_PREFIX').'companypay_log as l on c.pigcms_id = l.business_id and l.type="companypay" and l.status=0';
        $companyList = M('Companypay')->field('c.*,l.status as log_status,l.pigcms_id as log_id')->join($join)->where($where)->select();
        if(empty($companyList)){
            $this->error('该提现不存在');
        }
        $error_msg = [];
        $success_num = 0;
        $error_num = 0;
        foreach ($companyList as $company){
            // 检查状态
            if($company['status'] == 1){
                $error_num++;
                $error_msg[] = $company['nickname']. ' '.$company['phone'].'的申请已付款不能重复操作;';
                continue;
            }
            // 检查状态
            if($company['status'] == 10){
                $error_num++;
                $error_msg[] = $company['nickname']. ' '.$company['phone'].'的申请已申请提现不能重复操作;';
                continue;
            }

            if($company['status'] == 2){
                $error_num++;
                $error_msg[] = $company['nickname']. ' '.$company['phone'].'的已拒绝不能提现;';
                continue;
            }

            if($company['log_id'] && $company['log_status'] == 0){
                // 已付款 直接修改状态
                // 修改状态
                $where = [
                    'pigcms_id' => $company['pigcms_id']
                ];
                $saveData = [
                    'status' => 1,
                    'pay_time' => time()
                ];
                M('Companypay')->where($where)->save($saveData);
                continue;
            }


            // 写入日志
            $logData = [
                'business_id' => $company['pigcms_id'],
                'admin_id' => $this->system_session['id'],
                'type' => 'companypay',
            ];

            //请求第三方接口
            $param['money'] = $company['money'];
            $param['openid'] = $company['openid'];
            $param['name'] = $company['nickname'];
            $param['withdraw_name'] = "{$param['name']}提现";
            $param['partner_trade_no'] = date('YmdHis').$company['pigcms_id'];
            $param['withdraw_id'] = $company['pigcms_id'];
            $param['withdraw_type'] = 'company_pay';
            $param = array_merge($logData,$param);
            //开启了V3支付并且设置了V3秘钥
            if(C('config.withdraw_open_apiv3') == 1 && !empty(C('config.withdraw_weixin_apiv3'))){
                import('@.ORG.withdraw.WeixinPayV3');
                $weixinObj = new WeixinPayV3();
            }else{
                import('@.ORG.withdraw.Weixin');
                $weixinObj = new Weixin();
            }
            $result = $weixinObj->withdraw($param);
            if(empty($result['result'])){
                $error_num++;
                $error_msg[] = $company['nickname']. ' '.$company['phone'].'付款失败,接口错误：接口无返回内容;';

                // 添加日志
                $logData['status'] = 1;
                D('CompanypayLog')->addLog($logData,$result);
                continue;
            }
            if($result['result_code'] != 'SUCCESS'){
                $error_num++;
                if($result['result']['err_code'] == 'NAME_MISMATCH'){
                    $error_msg[] = $company['nickname']. ' '.$company['phone'].'的提现姓名与微信绑定实名认证姓名不一致，无法提现;';
                }else{
                    $error_msg[] = $company['nickname']. ' '.$company['phone'].'付款失败,接口错误：'.$result['result']['err_code_des'].';';
                }

                // 添加日志
                $logData['status'] = 1;
                D('CompanypayLog')->addLog($logData,$result);
                continue;
            }
            $success_num ++ ;

            // 添加日志
            $logData['status'] = C('customization.withdraw_apply') ? 2 : 0;//增加申请中状态
            D('CompanypayLog')->addLog($logData,$result);

            // 修改状态
            $where = [
                'pigcms_id' => $company['pigcms_id']
            ];
            $saveData = [
                'status' => C('customization.withdraw_apply') ? 10 : 1,//增加提现中状态
                'admin_id' => $this->system_session['id'],
//                'pay_time' => time()
            ];
            if(C('customization.withdraw_apply')){
                $saveData['apply_withdraw_time'] = time();
                $saveData['is_v3'] = C('config.withdraw_open_apiv3') == 1 && !empty(C('config.withdraw_weixin_apiv3')) ? 1 : 0;//是否是v3支付
            }else{
                $saveData['pay_time'] = time();
            }
            M('Companypay')->where($where)->save($saveData);

            if ($company['deliver_withdraw_id'] > 0 && !C('customization.withdraw_apply')) {
                $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
                $params = [
                    'withdrawId' => $company['deliver_withdraw_id'],
                    'status' => 1,
                    'params' => ['admin_id' => $this->system_session['id']]
                ];
                invoke_v20_service($model, $params);
            }elseif ($company['worker_withdraw_id'] > 0){
                D('merchant_workers_money')->where(['pigcms_id'=>$company['worker_withdraw_id']])->save(['desc'=>'提现至微信-成功']);
            }
        }
        $msg = C('customization.withdraw_apply') ? '申请提现<font color="green">'.$success_num."</font>条提现信息；<br>" : '付款成功<font color="green">'.$success_num."</font>条提现信息；<br>";
        
        if($error_msg){
			$msg .= C('customization.withdraw_apply') ? "申请提现<font color=\"red\">".$error_num.'</font>条信息，原因：'.implode(' ',$error_msg) : "付款失败<font color=\"red\">".$error_num.'</font>条信息，原因：'.implode(' ',$error_msg);
        }
        $this->success($msg);

    }



    public function edit_reason(){
        if(IS_POST){
            if(empty($_POST['reason'])){
                $this->error('理由不能为空！');
            }

            $where = [
                'pigcms_id'=>$_POST['id']
            ];
            $company = M('Companypay')->where($where)->find();
            if(empty($company)){
                $this->error('该提现不存在');
            }

            // 检查状态
            if($company['status'] == 1){
                $this->error('该提现已付款不能拒绝');
            }elseif($company['status'] == 2){
                $this->error('该提现已拒绝不能重复拒绝');
            }

            // 修改状态
            $data['reason'] = $_POST['reason'];
            $data['status'] = 2;
            $res = D('Companypay')->where($where)->save($data);
            if($res){
                if ($company['deliver_withdraw_id'] > 0) {
                    $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
                    $params = [
                        'withdrawId' => $company['deliver_withdraw_id'],
                        'status' => 2,
                        'params' => ['admin_id' => $this->system_session['id'], 'reason' => $data['reason']]
                    ];
                    invoke_v20_service($model, $params);
                }elseif ($company['worker_withdraw_id'] > 0){
                    $money = ($company['money'] + $company['service_charges'])/100;
                    $worker = D('Merchant_workers')->where(['merchant_worker_id'=>$company['pay_id']])->find();
                    if($worker){
                        D('Merchant_workers')->where(['merchant_worker_id'=>$company['pay_id']])->setInc('now_money',$money);
                        D('merchant_workers_money')->where(['pigcms_id'=>$company['worker_withdraw_id']])->save(['type'=>4,'desc'=>'提现至微信-提现失败，失败原因：'.$_POST['reason']]);
                    }else{
                        $this->error('技师不存在');
                    }
                }else{
                    // 返还金额
                    $money = ($company['money'] + $company['service_charges'])/100;
                    $user = D('User')->get_user($company['openid'], 'openid');
                    if($user){
                        $msg = '提现失败，失败原因：'.$_POST['reason'];
                        D('User')->add_money($user['uid'],$money,$msg);
                    }else{
                        $this->error('用户不存在');
                    }
                }
                $this->success('保存成功！');
            }else{
                $this->error($res['msg']);
            }
        }else{
            $this->assign('id',$_GET['id']);
            $this->display();
        }

    }

    //获取提现状态
    public function get_apply_status()
    {
        //获取记录信息
        $info = M('Companypay')->where(['pigcms_id'=>$_POST['id']])->find();
        $log_info = M('Companypay_log')->where(['type'=>'companypay','business_id'=>$_POST['id'],'status'=>2])->order('pigcms_id desc')->find();
        if(!$info || !$log_info){
            $this->AjaxReturn(['error'=>1,'msg'=>'数据查询失败']);
        }
        $applyInfo = json_decode($log_info['result'],true);
        if(!isset($applyInfo['transfer_batch']['batch_id']) && !isset($applyInfo['partner_trade_no'])){
            $this->AjaxReturn(['error'=>1,'msg'=>'未查询到转账批次id']);
        }
        //查询状态（先查询批次信息，如果批次信息查询到失败，再查询明细信息获取打款失败原因）
        if($info['is_v3']){
            import('@.ORG.withdraw.WeixinPayV3');
            $weixinObj = new WeixinPayV3();
            $batch_id = $applyInfo['transfer_batch']['batch_id'];
        }else{
            import('@.ORG.withdraw.Weixin');
            $weixinObj = new Weixin();
            $batch_id = $applyInfo['partner_trade_no'];
        }
        $result = $weixinObj->checkWithdrawApplyInfo($batch_id);
//            $result = ['error'=>0,'apply_status'=>1,'fail_reason'=>'','result'=>''];
        if($result['error']){//报错
            $this->AjaxReturn(['error'=>1,'msg'=>$result['msg']]);
        }
        if($result['apply_status'] != $info['status']){
            try {
                // 开启事务
                $database = D();
                $database->startTrans();
                //状态更新
                $update = ['status'=>$result['apply_status'],'fail_reason'=>$result['fail_reason']];
                if($result['apply_status']==1){
                    $update['pay_time'] = time();
                }
                M('Companypay')->where(['pigcms_id'=>$_POST['id']])->save($update);
                //增加日志
                $logData = [
                    'business_id' => $_POST['id'],
                    'type' => 'companypay',
                    'admin_id' => $this->system_session['id'],
                    'status' => $result['apply_status']==20 ? 1 : 0
                ];
                D('CompanypayLog')->addLog($logData, $result['result']);
                if ($info['deliver_withdraw_id'] > 0 && $result['apply_status'] == 1) {
                    $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
                    $params = [
                        'withdrawId' => $info['deliver_withdraw_id'],
                        'status' => 1,
                        'params' => ['admin_id' => $this->system_session['id']]
                    ];
                    invoke_v20_service($model, $params);
                }
                $database->commit();
            }catch(\Exception $e){
                $database->rollback();
                $this->AjaxReturn(['error'=>1,'msg'=>$e->getMessage()]);
            }
        }
        $this->AjaxReturn(['error'=>0]);
        exit;
    }
}