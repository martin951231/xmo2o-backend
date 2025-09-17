<?php
class House_village_custom_polygonModel extends Model{

	function get_custom_polygon($where,$field='') {
		$field = 'hvp.*,hvc.type,hvc.fid,hvc.bind_id,hvc.lng,hvc.lat';
		$custom_polygon = D('House_village_custom_polygon')
			->field($field)
			->join('as hvp LEFT JOIN '.C('DB_PREFIX').'house_village_custom as hvc ON hvc.id=hvp.custom_id')
			->where($where)
			->select();
		return $custom_polygon;
	}

	/**
	 * 修改网格绘制关联关系
	 * @param $where_bind
	 * @param $set_data
	 * @return bool
	 */
	function change_village_custom($where_bind, $set_data) {
		// 修改了关联
		$set = M('House_village_custom')->where($where_bind)->data($set_data)->save();
		if ($set) {
			return true;
		} else {
			fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/change_village_custom_err',true);
			fdump($where_bind,'api/log/'.date('Ymd').'/change_village_custom_err',true);
			fdump($set_data,'api/log/'.date('Ymd').'/change_village_custom_err',true);
			fdump(M('House_village_custom')->getLastSql(),'api/log/'.date('Ymd').'/change_village_custom_err',true);
			return false;
		}
	}
}