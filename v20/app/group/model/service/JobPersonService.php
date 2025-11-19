<?php
/**
 * 技师
 */

namespace app\group\model\service;

use app\marriage_helper\model\db\JobPerson;
use app\marriage_helper\model\db\MarriageCategory;

class JobPersonService
{
	//获取技师岗位分类
	public function getCate($limit = 5){
		$model = new MarriageCategory();
		$field = 'cat_id, pos_id, cat_name';
		$where = [
			['is_del', '=', 0],
			['status', '=', 0],
			// ['pos_id', '<>', ''],
		];
		$data = $model->getSome($where, $field, 'sort desc', 0, $limit);
		
		$return = [];
		if($data){
			$return = $data->toArray();
			foreach ($return as $key => $value) {
				$return[$key]['pos_id'] = !empty($value['pos_id']) ? explode(',', $value['pos_id']) : [];
			}
		}
		return $return;
	}

	public function getCateInfo($id){
		$model = new MarriageCategory();
		return $model->getOne([['cat_id','=', $id]])->toArray();
	}

	//获取一定数量的技师
	public function getJobPerson($pos_ids, $limit = 20){
		if(empty($pos_ids)) return [];
		$where = [
			['a.job_id', 'in', $pos_ids],
			['a.is_black', '=', 0],
			['a.status', '=', 2],
			['a.is_del', '=', 0]
		];
		$field = 'a.id, a.headimg, c.name as store_name, a.name, a.job_time, a.job_id, a.specialty, a.desc, b.name as job_name, a.uid';
		$data = (new JobPerson)->getPersonDetail($where, $field, 1, $limit);
		foreach ($data as $key => $value) {
			$year = ceil((time() - $value['job_time'])/(86400*365));
			$data[$key]['job_time'] = '从业'.$year.'年';
			$data[$key]['headimg'] = replace_file_domain($value['headimg']);
		}
		return $data;
	}

}