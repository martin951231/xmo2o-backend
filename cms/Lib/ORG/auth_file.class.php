<?php
/** 
 * 店铺的资质资料
 */
class auth_file
{
	/**根据店铺数据表的图片字段的一段来得到图片*/
	public function get_image_by_path($path, $image_type='-1')
	{
        if (strstr($path, 'upload/') !== false) {
            //含有upload
            $dir = '';
        } else {
            $dir = '/upload/authfile/';
        }

		if (!empty($path)) {
			$image_tmp = explode(',', $path);
            if (count($image_tmp) == 1) {
                //新格式
                $path = str_replace(',', '/', $path);
                $pathinfo = pathinfo($path);
                $image_tmp = [
                    0 => $pathinfo['dirname'],
                    1 => $pathinfo['basename']
                ];
            }

			if ($image_type == '-1') {
				$return['image'] = replace_file_domain($dir . $image_tmp[0] . '/' . $image_tmp['1']);
				$return['m_image'] = replace_file_domain($dir . $image_tmp[0] . '/m_' . $image_tmp['1']);
				$return['s_image'] = replace_file_domain($dir . $image_tmp[0] . '/s_' . $image_tmp['1']);
			} else {
				$return = replace_file_domain($dir . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1']) ;
			}
			return $return;
		} else {
			return false;
		}
	}
	/**根据店铺数据表的图片字段来得到图片*/
	public function get_allImage_by_path($path, $image_type='-1')
	{
		if (!empty($path)) {
			$tmp_pic_arr = explode(';', $path);
			foreach ($tmp_pic_arr as $key => $value) {
				$image_tmp = explode(',', $value);
				if ($image_type == '-1') {
					$return[$key]['image'] = file_domain() . '/upload/authfile/' . $image_tmp[0] . '/' . $image_tmp['1'];
					$return[$key]['m_image'] = file_domain() . '/upload/authfile/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
					$return[$key]['s_image'] = file_domain() . '/upload/authfile/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
				} else {
					$return[$key] = file_domain() . '/upload/authfile/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
				}
			}
			return $return;
		} else {
			return false;
		}
	}
	
	/**根据店铺数据表的图片字段来删除图片*/
	public function del_image_by_path($path)
	{
		if (!empty($path)) {
			$image_tmp = explode(',', $path);
			unlink('./upload/authfile/' . $image_tmp[0] . '/' . $image_tmp['1']);
			unlink('./upload/authfile/' . $image_tmp[0] . '/m_' . $image_tmp['1']);
			unlink('./upload/authfile/' . $image_tmp[0] . '/s_' . $image_tmp['1']);
			return true;
		} else {
			return false;
		}
	}
}
?>