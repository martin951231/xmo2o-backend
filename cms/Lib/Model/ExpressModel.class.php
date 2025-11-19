<?php
class ExpressModel extends Model{
	/* 得到快递公司列表 */
	public function get_express_list($farmat=false){
		$condition_express['status'] = 1;
		$express_list = $this->field(true)->where($condition_express)->order('`sort` DESC,`id` ASC')->select();
		return $farmat?$this->format_list($express_list):$express_list;
	}
	/* 得到单个快递公司 */
	public function get_express($express_type){
		$condition_express['id'] = $express_type;
		$condition_express['status'] = 1;
		$express_list = $this->field(true)->where($condition_express)->find();
		return $express_list;
	}

	public function format_list($list){
		$return = [];
		if($list){
			foreach ($list as $key => $value) {
				$return[$value['id']] = $value['name'];
			}
		}
		return $return;
	}

    /* 得到单个快递公司 */
    public function get_express_one($express_name){
        $condition_express['name'] = $express_name;
        $express_list = $this->field(true)->where($condition_express)->find();
        return $express_list;
    }
}

?>