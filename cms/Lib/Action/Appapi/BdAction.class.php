<?php
/*
 * BD业务推广接口
 *
 */
class BdAction extends BaseAction{
	private $BD;
	private $MONEY;
	private $BDINFO;
	public function _initialize(){
		parent::_initialize();
		if(empty($this->_uid)){
			$this->returnCode('20046009');
		}
		$this->BD = D("Bd");
		$this->MONEY = M("Bd_commission_record");
		$this->BDINFO = $this->BD->where(['uid'=>$this->_uid, 'status'=>1, 'is_del'=>0])->find();
		if(!$this->BDINFO){
			$this->returnCode('123123', [], L_('当前登录用户未绑定业务员或已被禁用'));	
		}
	}

	public function index(){
		$uid = I('uid');
		if(!$uid){
			$uid = $this->_uid;
		}
		else{
			$this->BDINFO = $this->BD->where(['uid'=>$uid, 'status'=>1, 'is_del'=>0])->find();
			if(!$this->BDINFO){
				$this->returnCode('123123', [], L_('当前指定用户未绑定业务员或已被禁用'));	
			}
		}

		$output = [
			'my_commission' => 0,
			'my_rate' => 0,
			'my_merchant' => 0,
			'my_son' => 0,
			'my_invitation_code' => ''
		];
		$output['my_commission'] = $this->MONEY->where(['bd_id' => $this->BDINFO['bd_id'], 'is_del' => 0])->sum('commission') ?: 0;
		$output['my_rate'] = $this->BDINFO['commission_rate'];
		$output['my_invitation_code'] = $this->BDINFO['invitation_code'];
		$output['my_merchant'] = M("Merchant")->where(['bd_id'=>$this->BDINFO['bd_id']])->count();
		$output['my_son'] = $this->BDINFO['type'] == '2' ? $this->BD->where(['pbd_id'=>$this->BDINFO['bd_id'], 'is_del'=>0,'status'=>1])->count() : -1;
		$this->returnCodeOk($output);
	}

	public function merchant_list(){
		$uid = I('uid');
		if(!$uid){
			$uid = $this->_uid;
		}
		else{
			$this->BDINFO = $this->BD->where(['uid'=>$uid, 'status'=>1, 'is_del'=>0])->find();
			if(!$this->BDINFO){
				$this->returnCode('123123', [], L_('当前指定用户未绑定业务员或已被禁用'));	
			}
		}
		$page = I("page") ?: 1;
		$limit = 10;

		$data = M("Merchant")->where(['bd_id'=>$this->BDINFO['bd_id']])->limit((($page-1)*$limit), $limit)->order('reg_time desc')->select();
		$list = [];
		foreach ($data as $key => $value) {
			$list[] = [
				'title' => $value['name'].'商户成功入驻',
				'date' => date('Y-m-d H:i:s', $value['reg_time'])
			];
		}
		$this->returnCodeOk($list);
	}

	public function commission_list(){
		$uid = I('uid');
		if(!$uid){
			$uid = $this->_uid;
		}
		else{
			$this->BDINFO = $this->BD->where(['uid'=>$uid, 'status'=>1, 'is_del'=>0])->find();
			if(!$this->BDINFO){
				$this->returnCode('123123', [], L_('当前指定用户未绑定业务员或已被禁用'));	
			}
		}
		$page = I("page") ?: 1;
		$limit = 10;

		$data = $this->MONEY->where(['bd_id' => $this->BDINFO['bd_id'], 'is_del' => 0])->limit((($page-1)*$limit), $limit)->order('addtime desc')->select();
		$list = [];
		foreach ($data as $key => $value) {
			$now_merchant = M("Merchant")->where(['mer_id'=>$value['mer_id']])->find();
			$list[] = [
				'title' => $now_merchant['name'].'商户完成消费，您获得佣金',
				'orderid' => '订单号：'.$value['order_id'],
				'money' => '+'.C("config.Currency_symbol").$value['commission'],
				'date' => $value['addtime']
			];
		}
		$this->returnCodeOk($list);
	}

	public function bd_list(){
		$page = I("page") ?: 1;
		$limit = 10;

		$data = $this->BD->where(['pbd_id'=>$this->BDINFO['bd_id'], 'is_del'=>0,'status'=>1])->limit((($page-1)*$limit), $limit)->order('addtime desc')->select();

		$list = [];
		foreach ($data as $key => $value) {
			$now_user = D("User")->get_user($value['uid']);
			$now_merchant = M("Merchant")->where(['mer_id'=>$value['mer_id']])->find();
			$list[] = [
				'uid' => $value['uid'],
				'avater' => $now_user['avatar'] ?: $this->config['site_url'].'/tpl/Wap/pure/static/images/pic-default.png',
				'name' => $value['bd_name'],
				'recoms' => M("Merchant")->where(['bd_id'=>$value['bd_id']])->count(),
				'recom_money' => $this->MONEY->where(['bd_id' => $value['bd_id'], 'is_del' => 0])->sum('commission') ?: 0
			];
		}
		$this->returnCodeOk($list);
	}
}