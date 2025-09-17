<?php
/* 
 * 得到店铺的图片
 * 
 */
class store_image{
	/*根据店铺数据表的图片字段的一段来得到图片*/
    public function get_image_by_path($path, $dir = 'store')
    {
        if (!empty($path)) {
            $image_tmp = explode(',', $path);
            if (count($image_tmp) == 2) {
                $return = file_domain() . '/upload/'.$dir.'/' . $image_tmp[0] . '/' . $image_tmp['1'];
            } else {
                $return = file_domain() . $path;
            }
            return $return;
        } else {
            return '';
        }
    }
	/*根据店铺数据表的图片字段来得到图片*/
	public function get_allImage_by_path($path, $dir = 'store'){
		if(!empty($path)){
			$tmp_pic_arr = explode(';',$path);
			foreach($tmp_pic_arr as $key=>$value){
			    if('http' === substr($value,0,4)){
					$return[$key] = $value;
				}elseif(stripos($value, ',')){
					$tmp_pic = explode(',', $value);
					$return[$key] = file_domain().'/upload/'.$dir.'/'.$tmp_pic[0].'/'.$tmp_pic[1];
				}else{
                    $return[$key] = file_domain().$value;
                }
			}
			return $return;
		}else{
			return false;
		}
	}
	/*根据店铺数据表的自增ID字段来得到图片*/
	public function get_image_by_id($id,$image_type){

	}
	
	/*根据店铺数据表的图片字段来删除图片*/
	public function del_image_by_path($path){
		if(!empty($path)){
			unlink('.'.$path);
			return true;
		}else{
			return false;
		}
	}
	/*根据店铺数据表的自增ID字段来得到图片*/
	public function del_image_by_id($id){
		
	}
}
?>