<?php
class House_village_user_attributeModel extends Model{
	
	/*得到小区下所有的业主列表*/
	public function get_attribute($village_id,$format=false){
		if(!$village_id){
			return array();
		}
		
        $return = $this->where(array('village_id'=>$village_id))->select();
        if ($format) {
	        $attribute_list = array();
	        foreach ($return as $key => $value) {
	        	$attribute_list[$value['pigcms_id']] = $value['name'];
	        }
	        $return = $attribute_list;
        }
		
		return $return;
	}

	
}

?>