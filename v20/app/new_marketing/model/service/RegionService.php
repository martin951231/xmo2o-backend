<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/25
 * Time: 13:19
 */

namespace app\new_marketing\model\service;


use app\common\model\db\Area;
use app\new_marketing\model\db\NewMarketingRegion;

class RegionService
{
	public function regionList($where, $order, $pageSize)
	{
		$newMarketingRegion = new NewMarketingRegion();
		$field              = 'r.*';
		$res                = $newMarketingRegion->getRegionList($where, $order, $pageSize, $field);
		$fatherArea         = $this->getFatherArea([], 0);

		$fatherArea = $this->convert_arr_key($fatherArea, 'area_id');
		foreach ($res as $value) {
			$region             = json_decode($value['region_id']);
			$value['region_id'] = $region;
			if (empty($region)) {
				$value['region'] = ['全国'];
			} else {

				foreach ($region as &$v) {
					$v = $fatherArea[$v]['area_name'];
				}

				$value['region'] = $region;
			}
			$value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
		}

		return $res;
	}

	/**
	 * @param array $region_arr
	 * @param int $type
	 * @return array
	 * type 1  查询全部
	 */
	public function getFatherArea($region_arr = [], $type = 1)
	{
		$area = new Area();
		if ($type == 1) {
			$condition_area = [['is_open', '=', 1], ['area_type', '=', 1], ['is_region', '=', 0]];
		} else {
			$condition_area = [['is_open', '=', 1], ['area_type', '=', 1]];
		}
		$order     = "area_sort DESC,area_id ASC";
		$area_data = $area->getSome($condition_area, 'area_id,area_name', $order)->toArray();

		if (!empty($region_arr)) {
			$condition_area2 = [['is_open', '=', 1], ['area_type', '=', 1], ['area_id', 'in', $region_arr]];
			$area_data2      = $area->getSome($condition_area2, 'area_id,area_name', $order)->toArray();

			$area_data = array_merge($area_data, $area_data2);
		}

		return $area_data;
	}


	/**
	 * @param $arr
	 * @param $key_name
	 * @return array
	 * 将数据库中查出的列表以指定的 id 作为数组的键名
	 */
	public function convert_arr_key($arr, $key_name)
	{
		$arr2 = [];
		foreach ($arr as $key => $val) {
			$arr2[$val[$key_name]] = $val;
		}

		return $arr2;
	}

	public function addRegionList($param, $region)
	{
		$area               = new Area();
		$newMarketingRegion = new NewMarketingRegion();

		$newMarketingRegion->add($param);
		if ($region) {
			$area_where[] = ['area_id', 'in', $region];
			$area->where($area_where)->save(['is_region' => 1]);
		}

		return true;
	}

	public function saveRegionList($param, $where, $region = [])
	{
		$area               = new Area();
		$newMarketingRegion = new NewMarketingRegion();

		$region_id = $newMarketingRegion->getOne($where, 'region_id')['region_id'];
		$region_id = json_decode($region_id);

		$newMarketingRegion->where($where)->save($param);

//		if ($region || (isset($param['is_del']) &&  $param['is_del']== 1)) {
		$area_where[] = ['area_id', 'in', $region_id];
		$area->where($area_where)->save(['is_region' => 0]);

		$area_where2[] = ['area_id', 'in', $region];
		$area->where($area_where2)->save(['is_region' => 1]);
//		}

		return true;
	}

	//获取全部省ID列表
	public function getAllProvinceIds($where) {
	    $list = (new NewMarketingRegion())->getAllProvinceIds($where);
	    return $list;
    }


}
