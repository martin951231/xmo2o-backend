<?php


namespace app\marriage_helper\model\service;


use app\marriage_helper\model\db\MarriagePlan;
use app\marriage_helper\model\db\MarriagePlanCategory;
use app\marriage_helper\model\db\MarriageUserPlan;
use app\marriage_helper\model\db\MarriageDate;

class UserMarryService
{
	//获取用户端的计划
	public function getPlan($uid, &$all_plan, &$complete_plan){
		if(empty($uid)) return [];
		$all_cate = (new MarriagePlanCategory)->getSome([['is_del', '=', 0]], 'cat_id, cat_name, times, times_type', 'sort desc');
		$return = [];
		$user_select = (new MarriageUserPlan)->getSome([['uid', '=', $uid], ['is_del', '=', 0], ['select', '=', 1]]);
		if($user_select){
			$user_select = $user_select->toArray();
			$user_select = array_column($user_select, 'select', 'plan_id');
			$complete_plan = count($user_select);
		}
		if($all_cate){
			$all_cate = $all_cate->toArray();
			foreach ($all_cate as $key => $value) {
				$all_cate[$key]['tips'] = '建议提前'.$value['times'].($value['times_type'] == '1' ? '天' : '个月');
				$where = [
					['cat_id', '=', $value['cat_id']], 
					['is_del', '=', 0],
					['uid', 'in', [0, $uid]]
				];
				$all_cate[$key]['sons'] = [];
				$plans = (new MarriagePlan)->getSome($where, 'plan_id, plan_title, link_txt, link', 'add_time asc');
				if($plans){
					$plans = $plans->toArray();
					$all_plan = $all_plan+count($plans);
					foreach ($plans as $plan) {
						$temp = [];
						$temp['plan_id'] = $plan['plan_id'];
						$temp['plan_title'] = $plan['plan_title'];
						$temp['link_txt'] = $plan['link_txt'];
						$temp['is_link'] = empty($plan['link']) ? 0 : 1;
						$temp['link'] = $plan['link'];
						$temp['select'] = isset($user_select[$plan['plan_id']]) ? true : false;
						$all_cate[$key]['sons'][] = $temp;
					}
				}
			}
			$return = $all_cate;
		}

		return $return;
	}

	public function add($uid, $plan_id, $select){
		$user_select = (new MarriageUserPlan)->getOne([['uid', '=', $uid],['plan_id', '=', $plan_id], ['is_del', '=', 0]]);
		if(!empty($user_select)){

			$data = [
				'select' => $select == '0' ? 0 : 1
			];
			(new MarriageUserPlan)->updateThis(['id'=>$user_select['id']], $data);
		}
		else{
			$data = [
				'plan_id' => $plan_id,
				'uid' => $uid,
				'select' => $select,
				'is_del' => 0
			];
			(new MarriageUserPlan)->add($data);
		}
		return ;
	}

	public function addUserPlan($uid, $cat_id, $plan_title){
		$data = [
			'cat_id' => $cat_id,
			'plan_title' => trim($plan_title),
			'is_system' => 0,
			'uid' => $uid,
			'add_time' => time(),
			'is_del' => 0
		];
		return (new MarriagePlan)->add($data);
	}

	public function addMarriageDate($param){
		$data = [
			'date' => $param['date'],
			'week' => $param['week'],
			'nongli' => $param['nongli'],
			'suici' => $param['suici'],
			'yi' => $param['yi'],
			'ji' => $param['ji'],
			'good' => $param['good'],
		];
		return (new MarriageDate)->add($data);
	}

	public function findMarriageDate($date){
		$where = [
			['date', '=', $date]
		];
		$res = (new MarriageDate)->getOne($where);
		if($res){
			return $res->toArray();
		}
		else {
			return [];
		}
	}
}