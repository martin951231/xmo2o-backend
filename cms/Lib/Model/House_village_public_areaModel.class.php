<?php
class House_village_public_areaModel extends Model{

	public $status_txt = array(
		0 => '关闭',
		1 => '正常',
	);

	public function getList($where=array(),$pageSize=20) {
		$ret = array();
		if (empty($where)) {
			return $ret;
		}
		import('@.ORG.merchant_page');
		$count = $this->where($where)->count();
		$p = new Page($count , $pageSize , 'page');
		$list = $this->where($where)->order('public_area_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
		if (empty($list)) {
			$list = array();
		}
		$ret['list'] = $list;
		$ret['pagebar'] = $p->show();
		return $ret;
	}

}