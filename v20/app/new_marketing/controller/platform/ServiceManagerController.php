<?php
/**
 * 业务经理
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/18
 * Time: 11:28
 */

namespace app\new_marketing\controller\platform;


use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingLog;
use app\new_marketing\model\service\MarketingPersonService;
use app\new_marketing\model\service\RegionalAgencyService;
use app\new_marketing\model\service\ServiceManagerService;
use think\facade\Db;

class ServiceManagerController extends AuthBaseController
{
	/**
	 * @return \json
	 * 业务经理列表
	 */
	public function serviceManagerList()
	{
		$serviceManagerService = new ServiceManagerService();
		try {
			$param['province_id'] = $this->request->param('province_id', 0, 'intval');
			$param['city_id']     = $this->request->param('city_id', 0, 'intval');
			$param['area_id']     = $this->request->param('area_id', 0, 'intval');
			$param['uid']         = $this->request->param('uid', 0, 'intval');//区域经理id
			$param['start_time']  = $this->request->param('start_time', '', 'trim');
			$param['end_time']    = $this->request->param('end_time', '', 'trim');
			$param['name']        = $this->request->param('name', '', 'trim');
			$pageSize        = $this->request->param('pageSize', 10, 'intval');
			$arr                  = $serviceManagerService->getDataList($param,$pageSize);
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 验证用户输入的是否正确
	 */
	public function findRight()
	{
		$regionalAgencyService = new RegionalAgencyService();
		$param['phone']        = $this->request->param('phone', 0, 'intval');
		$where                 = [['phone', '=', $param['phone']]];
		$msg                   = $regionalAgencyService->findUserRight($where);
		return api_output(0, $msg, 'success');
	}

	/**
	 * @return \json
	 * 新增业务经理
	 */
	public function addServiceManager()
	{
		$this->checkLogin();
		$regionalAgencyService = new RegionalAgencyService();
		$serviceManagerService = new ServiceManagerService();
		Db::startTrans();
		try {
			$param['name']        = $this->request->param('name', '', 'trim');
			$param['province_id'] = $this->request->param('province_id', 0, 'intval');
			$param['city_id']     = $this->request->param('city_id', 0, 'intval');
			$param['area_id']     = $this->request->param('area_id', 0, 'intval');
			$param['uid']         = $this->request->param('uid', 0, 'trim');
			$param['phone']       = $param['uid'];
			$param['note']        = $this->request->param('note', '', 'trim');
			$param['is_manager']  = 1;//业务经理
            (new MarketingPersonService())->delPer();//删除没有身份的业务人员
			// 统一验证手机号
			(new MarketingPersonService())->teamMemberCode(['uid' => $param['uid']]);

            $where = [['phone', '=', $param['uid'], ['status', '<>', 4]]];
			$msg   = $regionalAgencyService->findUserRight($where);

			if ($msg['status'] == 0) {
				return api_output_error(1003, '绑定手机号用户不存在');
			} else {
				$param['uid'] = $msg['data']['uid'];
			}

			// $serviceManagerService->checkPerson($param['uid']);
			$arr = $serviceManagerService->addServiceManager($param);
			Db::commit();
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}
	}


	/**
	 * @return \json
	 * 修改业务经理
	 */
	public function saveServiceManager()
	{
		$regionalAgencyService = new RegionalAgencyService();
		$serviceManagerService = new ServiceManagerService();
		Db::startTrans();
		try {
			$id                   = $this->request->param('id', 0, 'intval');
			$data['name']         = $this->request->param('name', '', 'trim');
//			$param['province_id'] = $this->request->param('province_id', 0, 'intval');
//			$param['city_id']     = $this->request->param('city_id', 0, 'intval');
//			$param['area_id']     = $this->request->param('area_id', 0, 'intval');
			$uid                  = $this->request->param('uid', 0, 'intval');
			$note        = $this->request->param('note', '', 'trim');
			if (!$id) {
				return api_output_error(1003, "缺少需要修改的信息id");
			} else {
				$person_id = $serviceManagerService->getPersonByBusiness([['id', '=', $id], ['is_del', '=', 0]]);

                if (!$person_id) {
                    return api_output_error(1003, '业务经理不存在');
                }

                (new NewMarketingPersonManager())->where([['id', '=', $id], ['is_del', '=', 0]])->update(['note' => $note]);

				$where = [['id', '=', $person_id], ['is_del', '=', 0]];

				$where2 = [['phone', '=', $uid]];
				$msg    = $regionalAgencyService->findUserRight($where2);

				if ($msg['status'] == 0) {
					return api_output_error(1003, '用户不存在');
				} else {
					$data['uid'] = $msg['data']['uid'];
				}
                $data['phone'] = $uid;

				$arr = $regionalAgencyService->saveRegionalAgencyOne($where, $data);
//				$arr = $serviceManagerService->doSaveBusiness([['id', '=', $id], ['is_del', '=', 0]], $param);
				Db::commit();
				return api_output(0, $arr, 'success');
			}
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 移除业务经理 页面
	 */
	public function delServiceManager()
	{
		$id= $this->request->param('id', 0, 'intval');
		$serviceManagerService = new ServiceManagerService();
		try {
			if (!$id) {
				return api_output_error(1003, "缺少需要移除的信息id");
			} else {
				$arr = $serviceManagerService->getFind($id);
				return api_output(0, $arr, 'success');
			}
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 做删除操作，如果有团队
	 */
	public function doDelServiceManager()
	{
		$serviceManagerService = new ServiceManagerService();
		$regionalAgencyService = new RegionalAgencyService();
		$id                    = $this->request->param('id', 0, 'intval');
		$person                = $this->request->param('person', 0, 'intval');
		$manager               = $this->request->param('manager', 0, 'intval');
		$param['is_del']       = 1;

		try {
			if (empty($person) || empty($manager)) {
				return api_output_error(1003, "请选择完整参数");
			}
			if (!$id) {
				return api_output_error(1003, "缺少需要移除的信息id");
			} else {
				$person_id = $serviceManagerService->getPersonByBusiness([['id', '=', $id], ['is_del', '=', 0]]);
				$person_sales_id = $serviceManagerService->getPersonBySales([['id', '=', $person], ['is_del', '=', 0]]);

				$where = [['id', '=', $person_id], ['is_del', '=', 0]];
				/**
				 * 先执行 替换操作 和接手操作
				 * 为团队绑定新的业务经理
				 */
				$serviceManagerService->getNewManager($id, ['manager_uid' => $manager]);
				(new MarketingPersonService)->teamManagementTransfer(['id' => $person_id, 'person_id' => $person_sales_id]);

//				$where=[['id','=',$id],['is_del','=',0]];
				$regionalAgencyService->saveRegionalAgencyOne($where, ['is_manager'=>0]);
				$arr = $serviceManagerService->doSaveBusiness([['id', '=', $id], ['is_del', '=', 0]], $param);
				return api_output(0, $arr, 'success');
			}
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * 没团队删除
	 */
	public function doDelManager()
	{
		$id                    = $this->request->param('id', 0, 'intval');
		$regionalAgencyService = new RegionalAgencyService();
		$serviceManagerService = new ServiceManagerService();
		$param['is_del']       = 1;
		try {
			if (!$id) {
				return api_output_error(1003, "缺少需要移除的信息id");
			} else {
				$person_id = $serviceManagerService->getPersonByBusiness([['id', '=', $id], ['is_del', '=', 0]]);

				$where = [['id', '=', $person_id], ['is_del', '=', 0]];
				$serviceManagerService->checkTeam($id);
				$regionalAgencyService->saveRegionalAgencyOne($where, ['is_manager'=>0]);
                $serviceManagerService->doDelBusiness($person_id);
				$arr = $serviceManagerService->doSaveBusiness([['id', '=', $id], ['is_del', '=', 0]], $param);
				return api_output(0, $arr, 'success');
			}
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * 降级执行操作
	 */
	public function doDownLevel()
	{
		$regionalAgencyService = new RegionalAgencyService();
		$serviceManagerService = new ServiceManagerService();
		$id                    = $this->request->param('id', 0, 'intval');//分表id
		$team_id               = $this->request->param('team_id', 0, 'intval');
		$manager_id            = $this->request->param('manager_id', 0, 'intval');//主表id
		$type                  = $this->request->param('type', 0, 'intval');//type=0 没团队  1 有团队

		Db::startTrans();
		try {
			if (!$id) {
				return api_output_error(1003, "缺少需要降级的信息id");
			}
			if ($type) {
				if (empty($team_id) || empty($manager_id)) {
					return api_output_error(1003, "请选择必选项");
				}
				$serviceManagerService->getNewManager($id, ['manager_uid' => $manager_id]);

			} else {
				if (empty($team_id)) {
					return api_output_error(1003, "请选择团队");
				}
			}

			$person_id = $serviceManagerService->getPersonByBusiness([['id', '=', $id], ['is_del', '=', 0]]);

			$where = [['id', '=', $person_id], ['is_del', '=', 0]];

			$data['is_del']       = 1;
			$data['team_id']      = $team_id;
			$param['is_manager']  = 0;
			$param['is_salesman'] = 1;
			$regionalAgencyService->saveRegionalAgencyOne($where, $param);

			$arr = $serviceManagerService->saveBusiness([['id', '=', $id]], $data, 0);

			$serviceManagerService->saveLevelLog($person_id, 0, 1);
			Db::commit();
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * 查找团队的人员 除了自己
	 */
	public function getTeamPerson()
	{
		$serviceManagerService = new ServiceManagerService();
		$id                    = $this->request->param('id', 0, 'intval');

		try {
			if (!$id) {
				return api_output_error(1003, "缺少需要升级的信息id");
			}
			$arr = $serviceManagerService->getFind($id);

			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * 升级操作
	 */
	public function doUpLevel()
	{
		$serviceManagerService = new ServiceManagerService();
		$regionalAgencyService = new RegionalAgencyService();

		$id              = $this->request->param('id', 0, 'intval');
		$person          = $this->request->param('person', 0, 'intval');
		$manager         = $this->request->param('manager', 0, 'intval');
		$province_id     = $this->request->param('province_id', 0, 'intval');
		$city_id         = $this->request->param('city_id', 0, 'intval');
		$area_id         = $this->request->param('area_id', 0, 'intval');
		$shop_percent    = $this->request->param('store_percent', 0, 'intval');
		$village_percent = $this->request->param('village_percent', 0, 'intval');
		$type            = $this->request->param('type', 0, 'intval'); //0无团队， 1有团队

		Db::startTrans();
		try {
			if (!$id) {
				return api_output_error(1003, "缺少需要升级的信息id");
			}
			if ($type) {
				if (!$person || !$manager || !$province_id) {//|| !$city_id || !$area_id
					return api_output_error(1003, "缺少需要升级的信息");
				}
			} else {
				if (!$province_id) {//|| !$city_id || !$area_id
					return api_output_error(1003, "缺少需要升级的信息");
				}
			}
			$person_id = $serviceManagerService->getPersonByBusiness([['id', '=', $id], ['is_del', '=', 0]]);
			$person_sales_id = $serviceManagerService->getPersonBySales([['id', '=', $person], ['is_del', '=', 0]]);

			$where = [['id', '=', $person_id], ['is_del', '=', 0]];

			$data['province_id']     = $province_id;
			$data['city_id']         = $city_id;
			$data['area_id']         = $area_id;
			$data['shop_percent']    = $shop_percent;
			$data['village_percent'] = $village_percent;
			$param['is_manager']     = 0;
			$param['is_agency']      = 1;

			if ($type) {
				$serviceManagerService->getNewManager($id, ['manager_uid' => $manager]);
				(new MarketingPersonService)->teamManagementTransfer(['id' => $person_id, 'person_id' => $person_sales_id]);
			}

			$regionalAgencyService->saveRegionalAgencyOne($where, $param);
			$arr = $serviceManagerService->saveBusiness([['id', '=', $id], ['is_del', '=', 0]], $data, 1);

			$serviceManagerService->saveLevelLog($person_id, 1, 3);
			Db::commit();
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}
	}
}