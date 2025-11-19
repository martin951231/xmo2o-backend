<?php


namespace app\marriage_helper\controller\api;
use app\marriage_helper\controller\api\ApiBaseController;
use app\common\model\service\UserService;
use app\marriage_helper\model\service\MarriageBudgetService;


class MarriageBudgetController extends ApiBaseController
{

	public function userBudget(){
		if(empty($this->userInfo)) throw_exception('用户未登录', '\\think\\Exception', 1002);

		$budget = $this->userInfo['budget'] > 0 ? get_format_number($this->userInfo['budget']/100) : '0';
		$output = [
			'budget' => $budget,
			'percent' => [],
			'list' => []
		];
		$percent = (new MarriageBudgetService)->getAllPercent();
		foreach ($percent as $key => $value) {
			$output['percent'][] = [
				'name' => $value['name'],
				'scale' => 	$value['scale']
			];
		}

		$user_budget = (new MarriageBudgetService)->getUserBudget($this->_uid);
		$user_budget_arr = array_column($user_budget, 'money', 'budget');
		foreach ($percent as $key => $value) {
			$output['list'][] = [
				'id' => $value['id'],
				'icon' => $value['icon'],
				'name' => $value['name'],
				'money' => isset($user_budget_arr[$value['id']]) ? get_format_number($user_budget_arr[$value['id']]/100) : '0',
				'display' => isset($user_budget_arr[$value['id']]) || $budget == '0' ? true : false,
				'scale' => $value['scale']
			];
		}
		//按金额倒序排序
		if($output['list']){
			$output['list'] = sortArray($output['list'], 'money', SORT_DESC);
		}


		return api_output(0, $output);
	}

	//设置用户预算
	public function setUserBudget(){
		if(empty($this->userInfo)) throw_exception('用户未登录', '\\think\\Exception', 1002);

		$money = $this->request->param('money', 0);

		$UserService = new UserService();
		$r = $UserService->updateThis(['uid'=>$this->_uid], ['budget'=>get_format_number($money*100)]);

		if($r !== false){
			(new MarriageBudgetService)->initUserBudget($this->_uid, $money);
		}
		return api_output(0, []);
	}

	//设置单个项目预算
	public function setSingleBudget(){
		if(empty($this->userInfo)) throw_exception('用户未登录', '\\think\\Exception', 1002);
		$budget_id = $this->request->param('budget_id');
		$money = $this->request->param('money', 0);
		if(empty($budget_id)) throw_exception('budget_id传输有误', '\\think\\Exception', 1003);
		if(empty($this->userInfo['budget'])) throw_exception('您需要先设置总预算', '\\think\\Exception', 1003);
		$val = (new MarriageBudgetService)->setSingleBudgetMoney($this->_uid, $budget_id, $money);
		$UserService = new UserService();
		$UserService->updateThis(['uid'=>$this->_uid], ['budget'=>($this->userInfo['budget']+$val)]);
		return api_output(0, []);
	}

	//设置不展示
	public function setBudgetDisplay(){
		if(empty($this->userInfo)) throw_exception('用户未登录', '\\think\\Exception', 1002);
		$budget_config = $this->request->param('budget_config');
		if(empty($budget_config)) throw_exception('budget_config传输有误', '\\think\\Exception', 1003);
		$budget_config = json_decode($budget_config, true);
		$val = (new MarriageBudgetService)->configBudget($this->_uid, $budget_config);
		if($val != '0'){
			$UserService = new UserService();
			$UserService->updateThis(['uid'=>$this->_uid], ['budget'=>($this->userInfo['budget']+$val)]);
		}
		return api_output(0, []);
	}
}