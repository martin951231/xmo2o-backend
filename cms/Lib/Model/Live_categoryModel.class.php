<?php
/*
 * 直播短视频首页分类
 *
 */
class Live_categoryModel extends Model{
	
	public function getList($where = []){
		$where['is_del'] = 0; 
		$list = $this->where($where)->select();

		$list = $list ?: [];
		return $list;
	}
}
?>