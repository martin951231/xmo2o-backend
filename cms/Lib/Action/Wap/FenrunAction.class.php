<?php
/**
 * Created by Goi.
 * User: Goi
 * Date: 2017年3月22日
 * Time: 11:28:07
 * Desc: 分润钱包
 */
class FenrunAction extends BaseAction{
	public function __construct()
	{
		parent::__construct();
		//if(!$this->config['open_score_fenrun']){
		//	$this->error_tips('非法访问！');
		//}
		if(empty($this->user_session)){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
		}
		//if(empty($this->user_session['openid'])) {
		//	$this->error_tips('您没有绑定微信');
		//}
		$this->user_session  = $now_user= D('User')->get_user($this->user_session['uid']);
		$this->assign('now_user',$now_user);
	}

	//分润钱包
	public function fenrun_money_list(){
		$this->display();
	}

	//用户奖励明细
	public function award_list(){
		$type = empty($_GET['type'])?1:$_GET['type'];
		$where['uid'] = $this->user_session['uid'];
		$where['type'] = $type;
		$where['income'] = 1;
		if($type==1){
			$where['l.uid'] = $where['uid'];
			unset($where['uid']);
			$return['award_list']	=	M('Fenrun_recommend_award_list')->field('l.*,u.phone,u.nickname as spreadname')->join('as l left join '.C('DB_PREFIX').'user u ON u.uid =l.type_id')->where($where)->order('id DESC')->select();
			$return['free_list']	=   M('Fenrun_free_award_money_list')->field('l.*,u.phone,u.nickname as spreadname')->join('as l left join '.C('DB_PREFIX').'user u ON u.uid =l.type_id')->where($where)->order('id DESC')->select();
		}else if($type==2){
			$where['l.uid'] = $where['uid'];
			unset($where['uid']);
			$return['award_list']	=	M('Fenrun_recommend_award_list')->field('l.*,u.phone,u.name as spreadname')->join('as l left join '.C('DB_PREFIX').'merchant u ON u.mer_id =l.type_id')->where($where)->order('id DESC')->select();
			$return['free_list']	=   M('Fenrun_free_award_money_list')->field('l.*,u.phone,u.name as spreadname')->join('as l left join '.C('DB_PREFIX').'merchant u ON u.mer_id =l.type_id')->where($where)->order('id DESC')->select();

		}else{
			$this->error_tips('非法访问');
		}

		//$return['award_list']	=	M('Fenrun_recommend_award_list')->where($where)->order('id DESC')->select();
		//$return['free_list']	=   M('Fenrun_free_award_money_list')->where($where)->order('id DESC')->select();
		$this->assign($return);
		$this->display();
	}

	/*分润转余额*/
	public function fenrun_to_balance(){
		if(!$this->config['open_fenrun_to_balance']) {
			$this->error_tips('平台未开启分润转余额！');
		}
		$now_user = $this->user_session;
		if($now_user['fenrun_money']<$this->config['min_fenrun_to_balance_money']) {
			$this->error_tips('分润钱包满'.$this->config['min_fenrun_to_balance_money'].C('config.Currency_txt').'才能转余额！');
		}

		$percent = $this->config['fenrun_to_balance_percent'];
		if(IS_POST && $_POST['money']){
				$money = $_POST['money'];
				if($money<$this->config['min_fenrun_to_balance_money']){
					$this->error('不能低于最低转余额金额 '.$this->config['company_least_money'].C('config.Currency_txt').'!');
				}
				if($money>$now_user['fenrun_money']){
					$this->error('转余额超出限额，请求失败！');
				}
				$data_fenrun['uid'] = $now_user['uid'];
				$data_fenrun['money'] = $money*((100-$percent)/100);
				$data_fenrun['fenrun_money'] = $money;
				$data_fenrun['des'] = "用户分润钱包转余额对账订单|用户ID ".$now_user['uid']." |转余额 ".$money.C('config.Currency_txt')."" ;
				if($percent>0){
					$data_fenrun['des'] .= '|手续费 '.$money*($percent)/100 .' 比例 '.$percent.'%';
				}

				$use_result = D('Fenrun')->fenrun_to_balance($data_fenrun);
				$this->success($use_result['msg'].'分润转余额成功');die;
		}else{
			$this->display();
		}
	}

	public function award_to_balance(){
		if(!$this->config['open_award_to_balance']) {
			$this->error_tips('平台未开启分佣转余额！');
		}
		$now_user = $this->user_session;


		if(IS_POST && $_POST['money']){
			$money = $_POST['money'];
			if($money<0){
				$this->error_tips('转余额金额出错！');
			}
			if($money>$now_user['free_award_money']){
				$this->error_tips('转余额超出限额，请求失败！');
			}
			$data_award['des'] = "用户可用佣金转余额对账订单|用户ID ".$now_user['uid']." |转账 ".$money.C('config.Currency_txt')."" ;
			$data_award['uid'] = $now_user['uid'];
			$data_award['money'] = $money;
			$use_result = D('Fenrun')->award_to_balance($data_award);
			echo json_encode($use_result);die;
		}else{
			$this->display();
		}
	}


	public function user_free_award_list(){
		$this->display();
	}

	public function ajax_fenrun_list(){
		$list	=	D('Fenrun')->fenrun_list($this->user_session['uid']);
		foreach($list['list'] as &$v){
			$v['add_time']	=	date('Y/m/d H:i',$v['add_time']);
		}
		echo json_encode($list);die;
	}

	public function ajax_user_free_award_list(){
		$list	=	D('Fenrun')->free_recommend_awards_list($this->user_session['uid']);
		foreach($list['list'] as &$v){
			$v['add_time']	=	date('Y/m/d H:i',$v['add_time']);
		}
		echo json_encode($list);die;
	}

	public function ajax_user_award_list(){
		$type = empty($_POST['type'])?1:$_POST['type'];
		$list	=	D('Fenrun')->recommend_awards_list($this->user_session['uid'],$type);
		foreach($list['list'] as &$v){
			$v['add_time']	=	date('Y/m/d H:i',$v['add_time']);
		}
		echo json_encode($list);die;
	}

	/*冻结首页，用户，商家统计*/
	public  function frozen_award_index(){
		$return['user_count'] = D('User_spread')->get_spread_num($this->user_session['openid'],$this->user_session['uid']);
		$return['mer_count'] = D('Merchant_spread')->get_spread_num($this->user_session['openid'],$this->user_session['uid']);
		$return['user_free_total'] = D('Fenrun')->get_free_total($this->user_session['uid'],1);
		$return['mer_free_total'] = D('Fenrun')->get_free_total($this->user_session['uid'],2);
		$return['user_award_total'] = D('Fenrun')->get_award_total($this->user_session['uid'],1);
		$return['mer_award_total'] = D('Fenrun')->get_award_total($this->user_session['uid'],2);

		$this->assign('return',$return);
		$this->display();
	}

	public function withdraw(){
		if($this->config['company_pay_open']=='0') {
			$this->error_tips('平台没有开启提现功能！');
		}

		if (!$this->user_session['phone']) {
			$referer = $_SERVER['HTTP_REFERER'];
			redirect(U('My/bind_user').'&referer='.urlencode($referer));
		}
		
		if($this->config['nmgzhcs_appid']){
			$referer = $_SERVER['HTTP_REFERER'];
			if(strpos($referer,'http') !== 0){
				$referer = $this->config['site_url'].$referer;
			}
			redirect($this->config['nmgzhcs_base_url'].'/wxmember/member_center/member_moneytocash?reref='.urlencode($referer));
		}
		
		$user_info = M('User')->where(array('uid'=>$this->user_session['uid']))->find();
		if($user_info['score_recharge_money']>0 && $user_info['now_money']<$user_info['score_recharge_money']){
			$this->error_tips('您目前没有可提现余额。');
		}
		$can_withdraw_money = $user_info['now_money']>=$user_info['score_recharge_money']?getFormatNumber($user_info['now_money']-$user_info['score_recharge_money']):getFormatNumber($user_info['now_money']);
		if($can_withdraw_money<0){
			$this->error_tips('您目前没有可提现余额！');
		}
		if ($user_info['frozen_time'] < $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] < $user_info['free_time']) {
			$user_info['can_withdraw_money'] = $can_withdraw_money-$user_info['frozen_money']>0? $can_withdraw_money-$user_info['frozen_money']:0;
		}else{
			$user_info['can_withdraw_money'] = $can_withdraw_money;
		}
		$this->assign('user_info',$user_info);

		if(IS_POST){
			$money = $_POST['money'];
			if(empty($_POST['truename'])){
				$this->error('真实姓名不能为空');
			}
			if($money<$this->config['company_least_money']){
				$this->error('不能低于最低提款额 '.$this->config['company_least_money'].C('config.Currency_txt').'!');
			}
			if($money>$can_withdraw_money){
				$this->error('提款超出限额，申请失败！');
			}

			if($user_info['score_recharge_money']>0 && $user_info['now_money']<$user_info['score_recharge_money']){
				$this->error('用户积分兑换余额数据错误，申请失败！');
			}
			if(!is_numeric($_POST['pay_type'])){
				$this->error('提现方式没有选择');
			}
			$data_companypay['type'] = 'user';
			$data_companypay['pay_type'] = $_POST['pay_type'];//0 银行卡，1 支付宝 2微信
			$data_companypay['pay_id'] = $user_info['uid'];
			$remark = '';
			if($_POST['pay_type']==0){
				$bank_id = $_POST['bank_id'];
				$bank = M('User_withdraw_account_list')->where(array('id'=>$bank_id))->find();

				$data_companypay['account'] = $bank['account'];

				if (empty($bank['account']) || empty($bank['account_name']) || empty($bank['remark'])) {
					$this->error('银行账号不全');
				}
				$remark = '开户名：' . $bank['account_name'] . ',开户行：' . $bank['remark'];
				
				if($bank['province_id']){
					$province = M('Area')->where(['area_id' => $bank['province_id']])->getField('area_name');
					$city = M('Area')->where(['area_id' => $bank['city_id']])->getField('area_name');
					
					$remark.= '，' . L_('开户省市：') . $province . $city;
					$remark.= '，' . L_('开户网点：') . $bank['branch'];
				}
					
				$user_money_desc = "提现至银行卡，账号：{$bank['remark']}{$bank['account']}，姓名：{$bank['account_name']}";
			}else if ($_POST['pay_type']==1){
				if(empty($_POST['alipay_account'])  ){
					$this->error('支付宝账号不全');
				}
				$data_companypay['account'] = $_POST['alipay_account'];
				$user_money_desc = "提现至支付宝，账号:{$_POST['alipay_account']},姓名:{$_POST['truename']}";
			}else{
				if(empty($user_info['openid'])  ){
					$this->error('没有绑定微信');
				}
				$data_companypay['account'] =  $user_info['openid'];
				$user_money_desc = "提现至微信,姓名:{$_POST['truename']}";
			}

			$data_companypay['name'] = $user_info['nickname']?$user_info['nickname']:$user_info['phone'];
			$data_companypay['truename'] = $_POST['truename'];
			$data_companypay['remark'] = $remark ;
			$data_companypay['phone'] = $user_info['phone'];
			$data_companypay['money'] = bcmul($money*((100-$this->config['company_pay_user_percent'])/100),100);
			$data_companypay['old_money'] = $money*100;
			$data_companypay['desc'] = "用户提现对账订单|用户ID ".$user_info['uid']." |转账 ".$money.C('config.Currency_txt')."" ;
			if($this->config['company_pay_user_percent']>0){
				$data_companypay['desc'] .= '|手续费 '.round((($data_companypay['old_money'] -  $data_companypay['money'])/100),2) .' 比例 '.$this->config['company_pay_user_percent'].'%';
				$fee =  '|手续费 '.round((($data_companypay['old_money'] -  $data_companypay['money'])/100),2) .' 比例 '.$this->config['company_pay_user_percent'].'%';
			}
			$data_companypay['status'] = 0;
			$data_companypay['add_time'] = time();

			// 开启事务
			$database = D('');
			$database->startTrans();
			$use_result = D('User')->user_money($user_info['uid'],$money,$user_money_desc.$fee,0,0,1);

			//notice

			if($use_result['error_code']){
				$database->rollback();// 回滚事务
				$this->error($use_result['msg']);
			}else{
				$msg = L_('申请成功，请等待审核！');
				if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
					//请求第三方接口
					$param['money'] = $data_companypay['money'];
					if($_POST['pay_type']==0){// 银行卡
						$param['money'] = $data_companypay['money'];
						$param['account'] = $bank['account'];
						$param['account_name'] = $bank['account_name'];
						$param['bank_name'] = $bank['remark'];
							
						import('@.ORG.withdraw.Weixin');
						$weixinObj = new Weixin();
						$param['partner_trade_no'] = date('YmdHis').$this->user_session['uid'];
						$result = $weixinObj->withdrawToBank($param);

						$data_companypay['third_id'] = $result['result']['payment_no'];//第三放订单id
						$data_companypay['cmms_amt'] = $result['result']['cmms_amt'];//第三放手续费

					}else if ($_POST['pay_type']==1){//支付宝
						$param['money'] = round($data_companypay['money']/100,2);
						$param['account'] = $data_companypay['account'];
						$param['truename'] = $data_companypay['truename'];
							
						import('@.ORG.withdraw.Alipay');
						$alipayObj = new Alipay();
						$param['partner_trade_no'] = date('YmdHis').$this->user_session['uid'];
						$result = $alipayObj->withdraw($param);
						$data_companypay['third_id'] = $result['pay_fund_order_id'];//第三放订单id
					
					}
					if($result['error']){
						$database->rollback();// 回滚事务
						$this->error_tips($result['msg']);
					}

					// 修改状态
					$data_companypay['status'] = 1;
					$data_companypay['pay_time'] =  time();
					
					$msg = L_('提现成功！');
				}

				$database->commit();// 完成事务
				M('Withdraw_list')->add($data_companypay);
				if($this->config['open_score_fenrun']){
					$url = U('My/money_list');
				}else{
					$url =U('My/transaction');
				}
				$this->success($msg,$url);
			}
		}else{
			$where['from'] = 0;
			$where['type'] = 0;
			$where['pay_id'] = $this->user_session['uid'];
			$bank_list = M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();
			$this->assign('bank_list',$bank_list);
            $companyPay = false;
            if($user_info['openid']){
                $companyPay = D('Companypay')->where(['openid' => $user_info['openid'], 'status' => ['IN', [0, 10]]])->find();
            }
            $this->assign('show_withdraw_tip', boolval($companyPay));
			$this->display();
		}
	}

	public function create_bank_account(){
		if(IS_POST){
			if(empty($_POST['account'])){
				$this->error(L_('银行卡号不能为空'));
			}
			if(empty($_POST['account_name'])){
				$this->error(L_('持卡人姓名不能为空'));
			}
			$data_companypay['from']     = 0;
			$data_companypay['type']     = 0;
			$data_companypay['pay_id']   = $this->user_session['uid'];
			$data_companypay['account_name'] = $_POST['account_name'];
			$data_companypay['account'] = $_POST['account'];
			$data_companypay['is_default'] = $_POST['is_default'];
			$data_companypay['add_time'] = $_SERVER['REQUEST_TIME'];
			$data_companypay['remark']    = $_POST['remark'];
			$data_companypay['province_id']  = $_POST['province_id'];
			$data_companypay['city_id']    = $_POST['city_id'];
			$data_companypay['branch']    = $_POST['branch'];


			if($_POST['bank_id']){
				$url  = U('bank_list');
				$result = M('User_withdraw_account_list')->where(array('id'=>$_POST['bank_id']))->save($data_companypay);
				if($result){
					$aid = $_POST['bank_id'];
				}else{
					$aid = false;
				}
			}else{
				$aid = M('User_withdraw_account_list')->add($data_companypay);
				$url  = U('withdraw');
			}
			if($_POST['f']!=1){
				$url  = U('bank_list');
			}

			if($aid){
				if($data_companypay['is_default']){
					$where['from'] = 0;
					$where['type'] = 0;
					$where['pay_id'] =  $this->user_session['uid'];
					$where['id'] = array('neq',$aid);
					M('User_withdraw_account_list')->where($where)->setField('is_default',0);
				}

					$this->success(L_('保存成功'),$url);

			}else{

				$this->error(L_('保存失败'));
			}
		}else{
			if($_GET['bank_id']){
				$where['id'] =  $_GET['bank_id'];
				$bank = M('User_withdraw_account_list')->where($where)->find();
				$this->assign('bank',$bank);
			}
			
			//省
			$province_list = D('Area')->get_arealist_by_areaPid(0,0);
			$this->assign('province_list', $province_list);
			
			//市
			$city_list = D('Area')->get_arealist_by_areaPid($bank ? $bank['province_id'] : $province_list[0]['area_id'],0);
			$this->assign('city_list', $city_list);
			
			$this->display();
		}
	}
	
	public function get_city_list(){
		$city_list = D('Area')->get_arealist_by_areaPid($_POST['province_id'],0);
		$this->success($city_list);
	}

	public function bank_list(){
		
		$pay_method = D('Config')->get_pay_method(0,0,true,true);
		if($pay_method['merchantwarrior']){
			$now_user = D('User')->get_user($this->user_session['uid']);
			redirect(packapp_url('hake_credit_pay/index.html?from=my',$now_user));
		}
		
		
		$where['from'] = 0;
		$where['type'] = 0;
		$where['pay_id'] =  $this->user_session['uid'];
		$bank_list = M('User_withdraw_account_list')->where($where)->select();

		$this->assign('bank_list',$bank_list);
		M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();
		$this->display();

	}

	public  function  get_bank_name(){
		$card_number = $_POST['card_number'];
		require_once APP_PATH . 'Lib/ORG/BankList.class.php';
		if($res = $this->bankInfo($card_number,$bankList)){
			$this->success($res);
		}else{
			$this->error('没有查询到相关银行');
		}
	}

	public function delete_bank_account(){
		$where['id'] = $_GET['bank_id'];
		if($this->config['open_allinyun']==1){
			$bank = M('User_withdraw_account_list')->where($where)->find();
			import('@.ORG.AccountDeposit.AccountDeposit');
			$deposit = new AccountDeposit('Allinyun');
			$allyun = $deposit->getDeposit();
			$allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
			$allyun->setUser($allinyun);
			$param['cardNo'] = $bank['account'];
			$res = $allyun->unbindBankCard($param);
			if($res['status']=='error'){
				$this->error_tips($res['message']);
			}

		}
		M('User_withdraw_account_list')->where($where)->delete();
		$this->success_tips(
				'删除成功',U('bank_list')
		);
	}

	function bankInfo($card,$bankList)
	{
		$card_8 = substr($card, 0, 8);
		if (isset($bankList[$card_8])) {
			return $bankList[$card_8];
		}
		$card_6 = substr($card, 0, 6);
		if (isset($bankList[$card_6])) {
			return $bankList[$card_6];

		}
		$card_5 = substr($card, 0, 5);
		if (isset($bankList[$card_5])) {
			return $bankList[$card_5];

		}
		$card_4 = substr($card, 0, 4);
		if (isset($bankList[$card_4])) {
			return $bankList[$card_4];

		}
		return null;
	}

}
?>