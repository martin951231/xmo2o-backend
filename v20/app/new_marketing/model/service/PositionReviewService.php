<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/24
 * Time: 10:46
 */

namespace app\new_marketing\model\service;


use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPositionReview;

class PositionReviewService
{
	public function getPositionReviewList($param, $where = [], $pageSize, $order = '')
	{
		$newMarketingPositionReview = new NewMarketingPositionReview();
		if (!empty($param['start_time']) && !empty($param['end_time'])) {
			$param['start_time'] = strtotime($param['start_time']);
            $param['end_time'] = strtotime($param['end_time']) + 86400;
			array_push($where, ['s.add_time', '>=', $param['start_time']]);
            array_push($where, ['s.add_time', '<', $param['end_time']]);
		} else if (!empty($param['end_time'])) {
			$param['end_time'] = strtotime($param['end_time']) + 86400;
			array_push($where, ['s.add_time', '<', $param['end_time']]);
		} else if (!empty($param['start_time'])) {
            $param['start_time'] = strtotime($param['start_time']);
            array_push($where, ['s.add_time', '>=', $param['start_time']]);
        }
		if ($param['identity']) {
			array_push($where, ['s.identity', '=', $param['identity']]);
		}
		if (!empty($param['name'])) {
			array_push($where, ['nmp.name', 'like', '%' . $param['name'] . '%']);
		}
		if ($param['status'] >= 0) {
			array_push($where, ['s.status', '=', $param['status']]);
		}

		$arr = $newMarketingPositionReview->getPositionReviewList($where, 's.*,nmt.name team_name,nmtt.name team_name1,nmp.name person_name,bp.total_performance,ps.total_performance as total_performance1',$pageSize,$order);

		foreach ($arr as &$value) {
            empty($value['team_name']) && $value['team_name'] = $value['team_name1'];
            empty($value['total_performance']) && $value['total_performance'] = $value['total_performance1'];
			$value['identity_name']     = $this->getIdentityName($value['identity']);
			$value['now_identity_name'] = $this->getIdentityName($value['now_identity']);

			$value['add_time'] = date("Y-m-d H:i:s",$value['add_time']);

			if ($value['status'] == 0) {
				$value['status_type'] = '待审核';
			} elseif ($value['status'] == 1) {
				$value['status_type'] = '已通过';
			} else {
				$value['status_type'] = '已拒绝';
			}
		}

		$data['manager_list'] = (new ServiceManagerService())->getCanManager();
		$data['areaList']     = (new RegionalAgencyService())->ajax_province();
		$data['arr']          = $arr;

		return $data;
	}

	/**
	 * @param $identity
	 * 1业务员 2业务经理 3区域代理
	 */
	public function getIdentityName($identity)
	{
		switch ($identity) {
			case 1:
				$return = '业务员';
				break;
			case 2:
				$return = '业务经理';
				break;
			case 3:
				$return = '区域代理';
				break;
			default:
				$return = '暂无职位';
				break;
		}

		return $return;
	}

	/**
	 * @param $id
	 * @return mixed
	 * 查找团队的人员 除了自己
	 */
	public function getFind($id)
	{
		$newMarketingPerson = new NewMarketingPerson();
		$now_identity = $this->getNowIdentity($id);

		$res = $newMarketingPerson->getPerson2(['p.id' => $id, 'p.is_del' => 0], 'bp.id,bp.team_id', $now_identity);

		if(!empty($res)){
		$where[]    = ['bp.team_id', '=', $res['team_id']];
		$where[]    = ['bp.is_del', '=', 0];
		$where[]    = ['bp.id', '<>', $res['id']];
		$where[]    = ['p.is_manager', '=', 1];
		$res_person = $newMarketingPerson->getCanManager($where);
		}else{
			$res_person = [];
		}
		return $res_person;
	}

	public function getPositionReviewUid($id,$field='pid')
	{
		$newMarketingPositionReview = new NewMarketingPositionReview();

		$res = $newMarketingPositionReview->getOne(['id' => $id], $field);

		return $res;
	}

	public function getNowIdentity($id)
	{
		$newMarketingPositionReview = new NewMarketingPositionReview();

		$res = $newMarketingPositionReview->getOne(['pid' => $id,'status'=>0], 'now_identity')['now_identity'];

		return $res;
	}

	public function updatePositionReview($where = [], $param = [])
	{
		$newMarketingPositionReview = new NewMarketingPositionReview();

		$res = $newMarketingPositionReview->where($where)->save($param);

		return $res;
	}

	public function addPositionReview($param){
		$newMarketingPositionReview = new NewMarketingPositionReview();

		$res = $newMarketingPositionReview->add($param);

		return $res;
	}
}