<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/18
 * Time: 11:30
 */

namespace app\new_marketing\model\service;


use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingLog;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingPersonAgency;
use app\new_marketing\model\db\NewMarketingTeam;
use think\route\Rule;
use function Matrix\add;

class ServiceManagerService
{
	/**
	 * @param $param
	 * @return mixed
	 * 业务经理列表
	 */
	public function getDataList($param,$pageSize)
	{
		$newMarketingPerson         = new NewMarketingPerson();
		$newMarketingPersonSalesman = new NewMarketingPersonSalesman();

		$where = [];
		if ($param['province_id'] && $param['city_id'] && $param['area_id']) {
			$where = [['s.province_id', '=', $param['province_id']], ['s.city_id', '=', $param['city_id']], ['s.area_id', '=', $param['area_id']]];
		}

		if (!empty($param['start_time']) && !empty($param['end_time'])) {
			$param['start_time'] = strtotime($param['start_time']. ' 00:00:00');
			array_push($where, ['s.add_time', '>', $param['start_time']]);
		}

		if (!empty($param['end_time'])) {
			$param['end_time'] = strtotime($param['end_time']. ' 23:59:59');
			array_push($where, ['s.add_time', '<', $param['end_time']]);
		}

		if ($param['uid']) {
			array_push($where, ['nmt.area_uid', '=', $param['uid']]);
		}
		if (!empty($param['name'])) {
			array_push($where, ['p.name', 'like', '%' . $param['name'] . '%']);
		}
        array_push($where, ['s.is_del', '=', 0]);
		array_push($where, ['p.is_del', '=', 0]);
		array_push($where, ['p.is_manager', '=', 1]);
		$all = $newMarketingPerson->getManagerList($where, "s.province_id,s.city_id,s.area_id,s.note,s.invitation_code,s.id,p.name manager_name,s.total_performance,s.add_time,nmt.id team_id,nmt.area_uid,nmt.name,nmt.is_del as team_del,p.phone",$pageSize);

		foreach ($all['list'] as $k => $v) {
			if ($v['add_time'] > 0) {
				$all['list'][$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
			}
			if ($v['area_uid'] > 0) {
				$all['list'][$k]['area_name'] = $newMarketingPerson->findNameById($v['area_uid']);
			} else {
				$all['list'][$k]['area_name'] = '-';
			}

			if (empty($v['team_id']) || $v['team_del'] == 1) {
                $all['list'][$k]['name'] = '-';
				$all['list'][$k]['team_id']    = '-';
				$all['list'][$k]['team_count'] = '-';
				$all['list'][$k]['total_performance'] = '-';
			} else {
				$all['list'][$k]['team_count'] = $newMarketingPersonSalesman->getCount(['team_id' => $v['team_id'],'is_del'=>0]);
			}

			if (empty($v['name'])) {
				$all['list'][$k]['name'] = '-';
			}
		}
		$list['list']         = $all['list'];
		$list['count']        = $all['count'];
		$list['areaList']     = (new RegionalAgencyService())->ajax_province();
		$list['area_user']    = $newMarketingPerson->getSome(['is_del' => 0, 'is_agency' => 1], 'id,name', 'id desc');
		$list['team_list']    = $this->getAllTeam();
		$list['manager_list'] = $this->getCanManager();
		return $list;
	}

	/**
	 * @param $param
	 * @return mixed
	 * 添加保存
	 */
	public function addServiceManager($param)
	{
		$newMarketingPerson         = new NewMarketingPerson();
		$newMarketingBusinessPerson = new NewMarketingPersonManager();

		$ret = $newMarketingPerson->where(['uid' => $param['uid'],'is_del' => 0])->value('id');
		if ($ret) {
			$newMarketingPerson->where(['id' => $ret])->save(['is_manager' => $param['is_manager']]);
		} else {
			$data['name']       = $param['name'];
			$data['is_manager'] = $param['is_manager'];
			$data['uid']        = $param['uid'];
			$data['phone']      = $param['phone'];

			$ret = $newMarketingPerson->add($data);
		}

		$arr['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
		$arr['province_id']     = $param['province_id'];
		$arr['city_id']         = $param['city_id'];
		$arr['area_id']         = $param['area_id'];
		$arr['note']            = $param['note'];
		$arr['add_time']        = time();
		$arr['update_time']     = time();
		$arr['person_id']       = $ret;

		$res = $newMarketingBusinessPerson->add($arr);

//		$add['person_id']       = $ret;
//		$add['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
//		$add['add_time']        = time();
//		$res = (new NewMarketingPersonSalesman())->add($add);

		return $res;
	}

	/**
	 * 验证是否为业务员
	 */
	public function checkPerson($uid)
	{
		$newMarketingPerson = new NewMarketingPerson();
		$newMarketingTeam   = new NewMarketingTeam();
		$res                = $newMarketingPerson->getPerson(['p.uid' => $uid, 'p.is_salesman' => 1, 'p.is_del' => 0], 'bp.team_id');

		$is_business_manager = $newMarketingPerson->getPerson(['p.uid' => $uid, 'p.is_manager' => 1, 'p.is_del' => 0], 'p.id');

		$is_is_agency = $newMarketingPerson->getPerson(['p.uid' => $uid, 'p.is_agency' => 1, 'p.is_del' => 0], 'p.id');

		if (!empty($res['team_id'])) {
			$team_name = $newMarketingTeam->getOne(['id' => $res['team_id']], 'name')['name'];

			throw_exception('该人员当前为' . $team_name . '团队业务员，可直接为他升级为业务经理');
		}

		if (!empty($is_business_manager)) {
			throw_exception('该人员当前为业务经理');
		}

		if (!empty($is_is_agency)) {
			throw_exception('该人员当前为区域代理');
		}
		return true;
	}

	/**
	 * @param $id
	 * @return mixed
	 * 查找团队的人员 除了自己
	 */
	public function getFind($id)
	{
		$newMarketingPerson = new NewMarketingPerson();

		$res = $newMarketingPerson->getPerson(['bp.id' => $id, 'p.is_manager' => 1, 'bp.is_del' => 0], 'bp.id,bp.team_id');

		$res_person = [];

		if (!empty($res)) {
			$where[]    = ['bp.team_id', '=', $res['team_id']];
			$where[]    = ['bp.is_del', '=', 0];
			$res_person = $newMarketingPerson->getCanSalesMan($where);
		}
//		$where2[] = ['identity','=',3];
//		$where2[] = ['is_del','=',0];
//		$where2[] = ['id','<>',$res['id']];
//		$res_manager = $newMarketingPerson->getSome(['id' => $id, 'identity' => 2, 'is_del' => 0], 'id,name','id asc');

		$data['person'] = $res_person;
//		$data['manager'] = $res_manager;

		return $data;
	}

	/**
	 * 替换团队的业务经理
	 */
	public function getNewManager($id, $data = [])
	{
		$newMarketingPerson = new NewMarketingPerson();
		$newMarketingTeam   = new NewMarketingTeam();

		$res = $newMarketingPerson->getPerson(['bp.id' => $id, 'p.is_manager' => 1, 'bp.is_del' => 0], 'bp.id,bp.team_id');

		if (!$res['team_id']) {
			throw_exception('该人无团队');
		}
		$ret = $newMarketingTeam->updateThis(['id' => $res['team_id']], $data);
		$this->doSaveBusiness([['person_id', '=', $data['manager_uid']]], ['team_id' => $res['team_id']]);

		if ($ret == false) {
			return false;
		}

		return true;
	}

	/**
	 * 验证此人是否有团队，在删除前验证
	 */
	public function checkTeam($id)
	{
		$newMarketingPerson = new NewMarketingPerson();

		$res = $newMarketingPerson->getPerson(['bp.id' => $id, 'p.is_manager' => 1, 'bp.is_del' => 0], 'bp.id,bp.team_id');

		if ($res['team_id']) {
			throw_exception('该人有团队，不可删除');
		}

		return true;
	}

	public function getAllTeam()
	{
		$newMarketingTeam = new NewMarketingTeam();
		$team_list        = $newMarketingTeam->getSome(['is_del' => 0]);

		return $team_list;
	}

	/**
	 * 获取没用的业务经理
	 */
	public function getCanManager()
	{
		$newMarketingPerson = new NewMarketingPerson();

		$res = $newMarketingPerson->getCanManager(['p.is_del' => 0, 'bp.team_id' => 0, 'p.is_manager' => 1]);

		return $res;
	}

	/**
	 * 升降级记录
	 */
	public function saveLevelLog($id, $status, $identity)
	{
		$newMarketingPerson = new NewMarketingPerson();
		$sel_manager        = $newMarketingPerson->getPerson(['p.id' => $id], 'bp.id,p.uid');

		if (!$sel_manager['uid']) {
			throw_exception('用户不存在');
		}

		$add['status']   = $status;
		$add['uid']      = $sel_manager['uid'];
		$add['identity'] = $identity;
		$add['add_time'] = time();
		$res             = (new NewMarketingLog())->add($add);

		return $res;
	}

	/**
	 * 通过分表id获取主表id
	 * 业务经理
	 */
	public function getPersonByBusiness($where)
	{
		$person_id = (new NewMarketingPersonManager())->getOne($where, 'person_id')['person_id'];
		return $person_id;
	}

	/**
	 * @param $where
	 * @return mixed
	 * 通过主表获取分表id
	 */
	public function getBusinessByPerson($where)
	{
		$id = (new NewMarketingPersonManager())->getOne($where, 'id')['id'];
		return $id;
	}

	/**
	 * @param $where
	 * @return mixed
	 * 通过主表获取分表id
	 */
	public function getSalesByPerson($where)
	{
		$id = (new NewMarketingPersonSalesman())->getOne($where, 'id')['id'];
		return $id;
	}

	/**
	 * @param $where
	 * @return mixed
	 * 通过分表id获取主表
	 */
	public function getPersonBySales($where)
	{
		$id = (new NewMarketingPersonSalesman())->getOne($where, 'person_id')['person_id'];
		return $id;
	}

	public function saveBusiness($where, $data, $status = 0)
	{//status 0 降级，1升级
		$arr = (new NewMarketingPersonManager())->where($where)->find();
//		$res = (new NewMarketingPersonManager())->where($where)->save(['is_del'=>1]);
		$res = (new NewMarketingPersonManager())->where($where)->delete();

		if ($status == 1) {
			if((new NewMarketingPersonAgency())->where(['person_id'=>$arr['person_id'],'is_del'=>0])->find()){
				throw_exception('该人已是区域代理');
			}

			//添加
			$add['person_id']       = $arr['person_id'];
			$add['invitation_code'] = $arr['invitation_code'];
			$add['province_id']     = $data['province_id'];
			$add['city_id']         = $data['city_id'];
			$add['area_id']         = $data['area_id'];
			$add['store_percent']   = $data['shop_percent'];
			$add['village_percent'] = $data['village_percent'];
			$add['add_time']        = time();
			(new NewMarketingPersonAgency())->add($add);
		} else {
			if((new NewMarketingPersonSalesman())->where(['person_id'=>$arr['person_id'],'is_del'=>0])->find()){
				throw_exception('该人已是业务员');
			}

			$add['person_id']       = $arr['person_id'];
			$add['invitation_code'] = $arr['invitation_code'];
			$add['team_id']         = $data['team_id'];
			$add['add_time']        = time();
			(new NewMarketingPersonSalesman())->add($add);
		}

		return $res;
	}

	public function doSaveBusiness($where, $data)
	{
		$res = (new NewMarketingPersonManager())->where($where)->save($data);

		return $res;
	}

    /**
     * @param $where
     * @param $data
     * @return bool
     * 删除操作
     */
    public function doDelBusiness($person_id)
    {
            $person=(new NewMarketingPerson())->getOne(['id'=>$person_id]);
            if(!empty($person)){
                $person=$person->toArray();
                if(($person['is_manager']==1 && $person['is_salesman']==0 && $person['is_agency']==0) || ($person['is_manager']==0 && $person['is_salesman']==0 && $person['is_agency']==0)){
                    (new NewMarketingPerson())->updateThis(['id'=>$person_id],['is_del'=>1]);
                }else{
                    (new NewMarketingPerson())->updateThis(['id'=>$person_id],['is_manager'=>0]);
                }
            }
        return true;
    }
	/**
	 * @param $where
	 * @param $data
	 * @return bool
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 * 业务员升级操作
	 */
	public function saveSalesMan($where, $data)
	{
		$arr = (new NewMarketingPersonSalesman())->where($where)->find();
//		$res = (new NewMarketingPersonSalesman())->where($where)->save(['is_del'=>1]);
		(new NewMarketingPersonSalesman())->where($where)->delete();

		if((new NewMarketingPersonManager())->where(['person_id'=>$arr['person_id'],'is_del'=>0])->find()){
			throw_exception('该人已是业务经理');
		}

		//添加
		$add['person_id']       = $arr['person_id'];
		$add['invitation_code'] = $arr['invitation_code'];
		$add['add_time']        = time();
		$add['province_id']     = $data['province_id'];
		$add['city_id']         = $data['city_id'];
		$add['area_id']         = $data['area_id'];
		$add['note']            = $arr['note'];
//		$add['team_id']         = $arr['team_id'];

		$res = (new NewMarketingPersonManager())->add($add);

		return $res;
	}
}