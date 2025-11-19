<?php

namespace app\common\controller\api;
use app\common\model\service\UserService;
use app\marriage_helper\model\service\UserMarryService;
use net\Http;

class UserMarriageController extends ApiBaseController
{
	//备婚计划首页
	public function index(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 112358755;

		$UserService = new UserService();
		$now_user = $UserService->getUser($uid);

		$output = [
			'marry_date' => $now_user['marry_date'] > 0 ? date('Y-m-d', $now_user['marry_date']) : '',
			'count_down' => '',
			'all_plan' => 0,
			'complete_plan' => 0,
			'list' => []
		];
		if($now_user['marry_date'] > 0 && $now_user['marry_date'] > time()){
			$output['count_down'] = '距离婚礼还有'.ceil(($now_user['marry_date']-time())/86400).'天';
		}elseif($now_user['marry_date'] <= time()){
			$output['count_down'] = '已完成';
		}
		$output['list'] = (new UserMarryService)->getPlan($uid, $output['all_plan'], $output['complete_plan']);

		return api_output(0, $output);
	}

	//设置结婚日期
	public function setMarryDate(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 112358755;
		$marry_date = $this->request->param('marry_date');
		if(empty($marry_date)) return api_output_error(1003, "参数错误");
		$UserService = new UserService();
		$UserService->updateThis(['uid'=>$uid], ['marry_date'=>strtotime($marry_date)]);
		return api_output(0, []);
	}

	//勾选任务
	public function selectPlan(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 112358755;
		$plan_id = $this->request->param('plan_id');
		$select = $this->request->param('select');
		if(empty($plan_id)) return api_output_error(1003, "参数错误");
		(new UserMarryService)->add($uid, $plan_id, $select);
		return api_output(0, []);
	}

	//添加任务
	public function addPlan(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 112358755;
		$cat_id = $this->request->param('cat_id');
		$plan_title = $this->request->param('plan_title');
		if(empty($cat_id) || empty($plan_title)) return api_output_error(1003, "参数错误");

		$r = (new UserMarryService)->addUserPlan($uid, $cat_id, $plan_title);
		
		return api_output(0, []);
	}

	//查询日期吉凶
	public function getDateInfo(){
		$month = $this->request->param('month');//月份
		if(empty($month)) return api_output_error(1003, "参数错误");
		$start = strtotime(date($month.'-1'));
		$end = $start + 86400*31;
		$output = [];
		$UserMarryService = new UserMarryService();
		for ($i=$start; $i <= $end; $i=$i+86400) { 
			$temp = [
				'day' => $i
			];
			$find = $UserMarryService->findMarriageDate($temp['day']);
			if(empty($find)){
				$date = date('Y-m-d', $temp['day']);
				list($year, $month, $day) = explode('-', $date);
				$url = "https://api.jisuapi.com/huangli/date";
		        $params = [
		            'appkey' => '6dbf00f64c806fa4',
		            'year' => $year,
		            'month' => $month,
		            'day' => $day
		        ];
		        $url .= "?" . http_build_query($params);
		        $http = new Http();
		        $result = $http->curlGet($url);
		        $json = json_decode($result, true);
		        if($json['status'] == '0'){
		        	$insert = [
		        		'date' => $temp['day'],
		        		'week' => '星期'.$json['result']['week'],
		        		'nongli' =>  $json['result']['nongli'],
		        		'suici' => implode(' ', $json['result']['suici']),
		        		'yi' => implode(',', $json['result']['yi']),
		        		'ji' => implode(',', $json['result']['ji']),
		        		'shengxiao' => $json['result']['shengxiao'],
		        		'yangli' => $json['result']['yangli'],
		        	];
		        	$insert['good'] = strpos($insert['yi'], '结婚') !== false || strpos($insert['yi'], '订婚') !== false || strpos($insert['yi'], '嫁娶') !== false ? 1 : 0;
		        	$UserMarryService->addMarriageDate($insert);
		        	$temp['date'] = date('Y-m-d', $temp['day']);
		        	$temp['week'] = $insert['week'];
		        	$temp['nongli'] = $insert['nongli'];
		        	$temp['suici'] = $insert['suici'];
		        	$temp['yi'] = explode(',', $insert['yi']);
		        	$temp['ji'] = explode(',', $insert['ji']);
		        	$temp['good'] = $insert['good'];
		        	$temp['shengxiao'] = $insert['shengxiao'];
		        	$temp['yangli'] = $insert['yangli'];
			    }
			    else{
			    	return api_output_error(1003, $json['msg']);
			    }
			}
			else{
				$temp['date'] = date('Y-m-d', $temp['day']);
	        	$temp['week'] = $find['week'];
	        	$temp['nongli'] = $find['nongli'];
	        	$temp['suici'] = $find['suici'];
	        	$temp['yi'] = explode(',', $find['yi']);
	        	$temp['ji'] = explode(',', $find['ji']);
	        	$temp['good'] = $find['good'];
	        	$temp['shengxiao'] = $find['shengxiao'];
	        	$temp['yangli'] = $find['yangli'];
			}
			$output[$temp['date']] = $temp;
		}

		return api_output(0, $output);
	}
}