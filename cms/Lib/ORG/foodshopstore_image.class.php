<?php

/*
 * 得到商品的图片
 *
 */
class foodshopstore_image
{

    /* 根据商品数据表的图片字段的一段来得到图片 */
    public function get_image_by_path($path, $image_type = '-1')
    {
        if (! empty($path)) {
            $image_tmp = explode(',', $path);
            if ($image_type == '-1') {
				if (strstr($image_tmp[0], '/upload/')) {
					$return['image'] = file_domain() . $path;
				    $return['m_image'] = file_domain() . $path;
				    $return['s_image'] = file_domain() . $path;
				}else{
                    $return['image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/' . $image_tmp['1'];
                    $return['m_image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
                    $return['s_image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
				}
            } else {
				if (strstr($image_tmp[0], '/upload/')) {
					$return = file_domain() . $path;
				}else{
					$return = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
				}
            }
            return $return;
        } else {
            return false;
        }
    }

    /* 根据商品数据表的图片字段来得到图片 */
    public function get_allImage_by_path($path, $image_type = '-1')
    {
        if (! empty($path)) {
            $tmp_pic_arr = explode(';', $path);
            foreach ($tmp_pic_arr as $key => $value) {
                $image_tmp = explode(',', $value);
                if ($image_type == '-1') {
                    $return[$key]['image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/' . $image_tmp['1'];
                    $return[$key]['m_image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
                    $return[$key]['s_image'] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
                } else {
                    $return[$key] = file_domain() . '/upload/foodshopstore/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
                }
            }
            return $return;
        } else {
            return false;
        }
    }

    /* 根据商品数据表的图片字段来删除图片 */
    public function del_image_by_path($path)
    {
        if (! empty($path)) {
            $image_tmp = explode(',', $path);
            unlink('./upload/foodshopstore/' . $image_tmp[0] . '/' . $image_tmp['1']);
            unlink('./upload/foodshopstore/' . $image_tmp[0] . '/m_' . $image_tmp['1']);
            unlink('./upload/foodshopstore/' . $image_tmp[0] . '/s_' . $image_tmp['1']);
            return true;
        } else {
            return false;
        }
    }
}
?>