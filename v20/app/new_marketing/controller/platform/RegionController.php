<?php
/**
 * 店铺价格分类列表 区域列表
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/25
 * Time: 13:15
 */

namespace app\new_marketing\controller\platform;


use app\new_marketing\model\service\ClassPriceService;
use app\new_marketing\model\service\RegionService;

class RegionController extends AuthBaseController
{
	/**
	 * @return \json
	 * 区域列表
	 */
	public function regionList()
	{
		$pageSize = $this->request->param('pageSize', 15, 'intval');
		$order    = $this->request->param('order', 0, 'intval');

		$regionService = new RegionService();
		try {
			$where = [['is_del', '=', 0]];


			if($order == 1){
				$order = 'add_time desc';
			}elseif($order == 2){
				$order = 'add_time asc';
			}else{
				$order = 'sort desc';
			}

			$arr = $regionService->regionList($where, $order, $pageSize)->toArray();
			$region_list        = $regionService->getFatherArea([], 1);
			$arr['region_list'] = $region_list;
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 修改或者添加
	 */
	public function addRegion()
	{
		$regionService = new RegionService();
		$name          = $this->request->param('name', '', 'trim');
		$region        = $this->request->param('region', '');
		$status        = $this->request->param('status', 1, 'intval');
		$id            = $this->request->param('id', 0, 'intval');
		$sort          = $this->request->param('sort', 0, 'intval');
		try {
			if (empty($name)) {
				return api_output_error(1003, '请填写名称');
			}

			$param['name']      = $name;
			$param['region_id'] = json_encode($region);
			$param['status']    = $status;
			$param['sort']      = $sort;
			if ($id) {//修改
				$param['update_time'] = time();

				$arr = $regionService->saveRegionList($param, ['id' => $id], $region);
			} else {//添加
				$param['add_time']    = time();
				$param['update_time'] = time();

				$arr = $regionService->addRegionList($param, $region);
			}

			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 店铺价格设置列表
	 */
	public function classPriceList()
	{
		$classPriceService = new ClassPriceService();
		$region_id         = $this->request->param('id', 0, 'intval');
		$pageSize          = $this->request->param('pageSize', 15, 'intval');

		try {
			$arr = $classPriceService->getClassPriceList($region_id, $pageSize);

			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 修改/添加店铺价格
	 */
	public function addClassPrice()
	{
		$classPriceService      = new ClassPriceService();
		$id                     = $this->request->param('id', 0, 'intval');
		$param['region_id']     = $this->request->param('region_id', 0, 'intval');
		$param['class_id']      = $this->request->param('cat_id', 0, 'intval');
		$param['year_price']    = $this->request->param('year_price', 0);
		$param['discount_type'] = $this->request->param('discount_type', 3, 'intval');
		$param['discount_rate'] = $this->request->param('discount_rate', 0);
		$param['manual_price']  = $this->request->param('manual_price', '', 'trim');
		$param['sort']          = $this->request->param('sort', 0, 'intval');
		if (!$param['region_id'] || !$param['class_id']) {
			return api_output_error(1003, '参数错误');
		}
		$param['add_time']    = time();
		$param['update_time'] = time();
		try {
			if (empty($param['region_id'])) {
				return api_output_error(1003, '区域不存在');
			}
			if ($param['manual_price']) {
				$param['manual_price'] = json_encode($param['manual_price']);
			}

			if (!$id) {
				$classPriceService->addClassPrice($param);
			} else {
				$classPriceService->saveClassPrice($param, $id);
			}
			return api_output(0, [], 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	public function delRegion()
	{
		$id            = $this->request->param('id', 0, 'intval');
		$regionService = new RegionService();

		try {
			if (empty($id)) {
				return api_output_error(1003, '请选择你要删除的id');
			}
			$param['update_time'] = time();
			$param['is_del']      = 1;

			$arr = $regionService->saveRegionList($param, ['id' => $id]);

			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

}