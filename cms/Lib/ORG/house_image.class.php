<?php
class house_image
{
	public function get_image_by_path($path)
    {
        if (!empty($path)) {
            $image_tmp = explode(',',$path);
            $image_count=count($image_tmp);
            $return = file_domain().'/upload/house/';
            if($image_tmp[0]){
                $return .=$image_tmp[0];
            }
            if($image_count>1){
                $return .='/'.$image_tmp[1];
            }
            return $return;
        } else {
            return false;
        }
    }
	
	public function del_image_by_path($path)
	{
		if (!empty($path)) {
			$image_tmp = explode(',', $path);
			unlink('./upload/house/' . $image_tmp[0] . '/' . $image_tmp['1']);
			return true;
		} else {
			return false;
		}
	}
}
?>