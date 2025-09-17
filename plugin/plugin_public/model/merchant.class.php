<?php
class merchant{
	public function get_info($id){
		$now_merchant = $this->field(true)->where(array('mer_id'=>$id))->find();
		if(empty($now_merchant)){
			return false;
		}
		return $now_merchant;
	}
}
?>