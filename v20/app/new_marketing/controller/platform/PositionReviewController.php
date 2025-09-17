<?php
/**
 * 职位审核列表
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/24
 * Time: 10:45
 */

namespace app\new_marketing\controller\platform;


use app\new_marketing\model\service\PositionReviewService;
use app\new_marketing\model\service\RegionalAgencyService;
use app\new_marketing\model\service\ServiceManagerService;
use think\facade\Db;

class PositionReviewController extends AuthBaseController
{
	/**
	 * @return \json
	 * 获取审核列表
	 */
	public function positionReviewList()
	{
		$newMarketingPositionReview = new PositionReviewService();
		Db::startTrans();
		try {
			$param['identity']   = $this->request->param('identity', 0, 'intval');
			$status              = $this->request->param('status', '', 'trim');
			$param['name']       = $this->request->param('name', 0, 'trim');
			$param['start_time'] = $this->request->param('start_time', '', 'trim');
			$param['end_time']   = $this->request->param('end_time', '', 'trim');
			$pageSize            = $this->request->param('pageSize', 15, 'intval');
			$order               = $this->request->param('order', 0, 'intval');

			if ($order == 2) {
				$order = 's.add_time asc';
			} else {
                $order = 's.add_time desc';
            }

			if ($status == '') {
				$param['status'] = -1;
			} else {
				$param['status'] = $status;
			}
			$arr = $newMarketingPositionReview->getPositionReviewList($param, [], $pageSize, $order);
			Db::commit();
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 获取本团队的所有人
	 */
	public function getTeamList()
	{
		$newMarketingPositionReview = new PositionReviewService();

		try {
			$pid = $this->request->param('pid', 0, 'intval');
			if (!$pid) {
				return api_output_error(1003, "缺少id");
			}

			/**
			 * 判断是否有商家接手，如果没有传空数组
			 */
			$arr = $newMarketingPositionReview->getFind($pid);
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 同意操作
	 */
	public function reviewAgree()
	{
		$serviceManagerService      = new ServiceManagerService();
		$newMarketingPositionReview = new PositionReviewService();
		$regionalAgencyService      = new RegionalAgencyService();

		$id              = $this->request->param('id', 0, 'intval');
		$pid             = $this->request->param('pid', 0, 'intval');//接手的id
		$manage_id       = $this->request->param('manage_id', 0, 'intval');//替换业务经理id
		$province_id     = $this->request->param('province_id', 0, 'intval');
		$city_id         = $this->request->param('city_id', 0, 'intval');
		$area_id         = $this->request->param('area_id', 0, 'intval');
		$shop_percent    = $this->request->param('shop_percent', 0, 'intval');
		$village_percent = $this->request->param('village_percent', 0, 'intval');
		$agree_reason    = $this->request->param('agree_reason', '', 'trim');
		$type            = $this->request->param('type', 0, 'intval');//0升级业务经理 1升级区域代理
		Db::startTrans();
		try {

			if (empty($id)) {
				return api_output_error(1003, "缺少升级信息");
			}

			$this_pid = $newMarketingPositionReview->getPositionReviewUid($id)['pid'];

			$data['province_id']     = $province_id;
			$data['city_id']         = $city_id;
			$data['area_id']         = $area_id;
			$data['shop_percent']    = $shop_percent;
			$data['village_percent'] = $village_percent;

			if ($type > 0) {//升级区域代理
				if (empty($manage_id)) {
					return api_output_error(1003, "缺少升级信息");
				}
				$manager_id = $serviceManagerService->getBusinessByPerson([['person_id', '=', $this_pid], ['is_del', '=', 0]]);//业务经理表id

				$param['is_manager'] = 0;
				$param['is_agency']  = 1;
				$identity            = 3;

				$serviceManagerService->getNewManager($manager_id, ['manager_uid' => $manage_id]);

				$serviceManagerService->saveBusiness([['id', '=', $manager_id], ['is_del', '=', 0]], $data, 1);
			} else {//升级业务经理
				$manager_id = $serviceManagerService->getSalesByPerson([['person_id', '=', $this_pid], ['is_del', '=', 0]]);//业务员表id

				$param['is_manager']  = 1;
				$param['is_salesman'] = 0;
				$identity             = 2;

				$serviceManagerService->saveSalesMan([['id', '=', $manager_id], ['is_del', '=', 0]], $data);
			}

			$where = [['id', '=', $this_pid], ['is_del', '=', 0]];
			$arr   = $regionalAgencyService->saveRegionalAgencyOne($where, $param);

			if ($arr) {//同意
				$where_new                 = [['id', '=', $id]];
				$param_new['status']       = 1;
				$param_new['agree_reason'] = $agree_reason;
				$param_new['update_time']  = time();

				$res = $newMarketingPositionReview->updatePositionReview($where_new, $param_new);

				$serviceManagerService->saveLevelLog($this_pid, 1, $identity);

				Db::commit();
				return api_output(0, $res, 'success');

			} else {
				Db::rollback();
				return api_output_error(1003, '审核失败');
			}
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}


	}

	/**
	 * @return \json
	 * 拒绝操作
	 */
	public function reviewDisagree()
	{
		$newMarketingPositionReview = new PositionReviewService();
		$id                         = $this->request->param('id', 0, 'intval');
		try {
			if (empty($id)) {
				return api_output_error(1003, "缺少信息");
			}

			$where_new           = [['id', '=', $id]];
			$param_new['status'] = 2;
			$param_new['update_time'] = time();
//			$param_new['agree_reason'] = $agree_reason;

			$res = $newMarketingPositionReview->updatePositionReview($where_new, $param_new);

			return api_output(0, $res, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}
}