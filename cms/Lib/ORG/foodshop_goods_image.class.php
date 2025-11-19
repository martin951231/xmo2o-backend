<?php
/* 
 * 得到商品的图片
 * 
 */
class foodshop_goods_image
{
	/*根据商品数据表的图片字段的一段来得到图片*/
	public function get_image_by_path($path, $image_type='-1')
	{
		if (!empty($path)) {
		    $image_tmp = explode(',', $path);
		    $filepath = 'foodshop_goods';
		    if (strstr($image_tmp[0], 'sysgoods_')) {
		        $filepath = 'sysgoods';
		    }
			if ($image_type == '-1') {
			    if (strstr($image_tmp[0], '/upload/goods')) {
					// 外卖同步到餐饮
			        $path = str_replace([file_domain() . '/upload/goods/', '/upload/goods/'], '', $path);
                    $filepath = 'goods';
				    $return['image'] = file_domain() . '/upload/' . $filepath. '/' . $path;
				    $return['m_image'] = file_domain() . '/upload/' . $filepath. '/' . $path;
				    $return['s_image'] = file_domain() . '/upload/' . $filepath. '/' . $path;
			        
			    }elseif (strstr($image_tmp[0], '/upload/')) {
			    	$return['image'] = file_domain() . $path;
				    $return['m_image'] = file_domain() . $path;
				    $return['s_image'] = file_domain() . $path;
			    }else{
				    $return['image'] = file_domain() . '/upload/' . $filepath. '/' . $image_tmp[0] . '/' . $image_tmp['1'];
				    $return['m_image'] = file_domain() . '/upload/' . $filepath. '/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
				    $return['s_image'] = file_domain() . '/upload/' . $filepath. '/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
				}
			} else {
				if (strstr($image_tmp[0], '/upload/goods')) {
					// 外卖同步到餐饮
			        $path = str_replace([file_domain() . '/upload/goods/', '/upload/goods/'], '', $path);
                    $filepath = 'goods';
			    	$return = file_domain() . '/upload/' . $filepath. '/' . $path;
			    }elseif (strstr($image_tmp[0], '/upload/')) {
			    	$return = file_domain() . $path;
			    }else{
			    	$return = file_domain() . '/upload/' . $filepath. '/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
				}
			}
			return $return;
		} else {
			return false;
		}
	}
	/*根据商品数据表的图片字段来得到图片*/
	public function get_allImage_by_path($path, $image_type='-1')
	{
		if (!empty($path)) {
			$tmp_pic_arr = explode(';', $path);
			foreach ($tmp_pic_arr as $key => $value) {
			    $image_tmp = explode(',', $value);
			    $filepath = 'foodshop_goods';
			    if (strstr($image_tmp[0], 'sysgoods_')) {
			        $filepath = 'sysgoods';
			    }
				if ($image_type == '-1') {
				    $return[$key]['image'] = file_domain() . '/upload/' . $filepath . '/' . $image_tmp[0] . '/' . $image_tmp['1'];
				    $return[$key]['m_image'] = file_domain() . '/upload/' . $filepath . '/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
				    $return[$key]['s_image'] = file_domain() . '/upload/' . $filepath . '/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
				} else {
				    $return[$key] = file_domain() . '/upload/' . $filepath . '/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
				}
			}
			return $return;
		} else {
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function get_image_by_id($id, $image_type)
	{
		$database_goods = D('Foodshop_goods');
		$condition_goods['goods_id'] = $id;
		$now_goods = $database_goods->field('`image`')->where($condition_goods)->find();
		return $this->get_image_by_path($now_goods['image'], $image_type);
	}
	
	/*根据商品数据表的图片字段来删除图片*/
	public function del_image_by_path($path)
	{
		if (!empty($path)) {
			$image_tmp = explode(',', $path);
			unlink('./upload/foodshop_goods/' . $image_tmp[0] . '/' . $image_tmp['1']);
			unlink('./upload/foodshop_goods/' . $image_tmp[0] . '/m_' . $image_tmp['1']);
			unlink('./upload/foodshop_goods/' . $image_tmp[0] . '/s_' . $image_tmp['1']);
			return true;
		} else {
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function del_image_by_id($id)
	{
		$database_goods = D('Foodshop_goods');
		$condition_goods['goods_id'] = $id;
		$now_goods = $database_goods->field('`image`')->where($condition_goods)->find();
		return $this->del_image_by_path($now_goods['image']);
	}
}
?>