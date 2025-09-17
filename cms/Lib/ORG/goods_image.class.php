<?php
/* 
 * 得到商品的图片
 * 
 */
class goods_image
{
	/*根据商品数据表的图片字段的一段来得到图片*/
	public function get_image_by_path($path, $image_type='-1')
	{
		if (!empty($path)) {
            $filepath = 'goods';
            if (strstr($path, 'sysgoods_')) {
                $filepath = 'sysgoods';
            }
			if ($image_type == '-1') {
                if ('http' === substr($path, 0, 4)) {
                    $return['image'] = replace_file_domain($path);
                    $return['m_image'] = replace_file_domain($path);
                    $return['s_image'] = replace_file_domain($path);
                } else if (strpos($path, ',') !== false) {
					$temp_path = explode(',',$path);
					if (strpos($path,'/upload/' . $filepath. '/')!==false){
						$return['image'] = file_domain() .$temp_path[0].'/'.$temp_path[1];
						$return['m_image'] = file_domain() .$temp_path[0].'/m_'.$temp_path[1];
						$return['s_image'] = file_domain() .$temp_path[0].'/s_'.$temp_path[1];
					}else{		
						$return['image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/'.$temp_path[1];
						$return['m_image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/m_'.$temp_path[1];
						$return['s_image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/s_'.$temp_path[1];
					}	
				}else{
					$return['image'] = file_domain() . $path;
					$return['m_image'] = file_domain() . $path;
					$return['s_image'] = file_domain() . $path;
				}
			} else {
				if('http' === substr($path,0,4)){
					$return = $path;
				}else{
					if (strpos($path,',')!==false) {
						if (strpos($path,'/upload/' . $filepath. '/')!==false){
							$path = explode(',',$path);
							$return = file_domain() .$path[0].'/'.$path[1];
						}else{		
							$path = explode(',',$path);
							$return = file_domain() .'/upload/' . $filepath. '/'.$path[0].'/'.$path[1];
						}
					}else{
						$return = file_domain() . $path;
					}
				}
			}
			return $return;
		} else if(C('config.goods_default_image')){
			return C('config.goods_default_image');
        } else if (C('GOODS_DEFAULT_IMAGE')) {
            return C('GOODS_DEFAULT_IMAGE');
        }else {
			return false;
		}
	}
	/*根据商品数据表的图片字段来得到图片*/
	public function get_allImage_by_path($path, $image_type='-1')
	{
		if (!empty($path)) {
			$tmp_pic_arr = explode(';', $path);
			foreach ($tmp_pic_arr as $key => $value) {
				$return[$key] = $this->get_image_by_path($value,$image_type);
			}
			return $return;
		} else if(C('config.goods_default_image')){
			if ($image_type == '-1') {
				$return[0]['image'] = C('config.goods_default_image');
				$return[0]['m_image'] = C('config.goods_default_image');
				$return[0]['s_image'] = C('config.goods_default_image');
			} else {
				$return[0] = C('config.goods_default_image');
			}
			return $return;
		} else {
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function get_image_by_id($id, $image_type)
	{
		$database_goods = D('Shop_goods');
		$condition_goods['goods_id'] = $id;
		$now_goods = $database_goods->field('`image`')->where($condition_goods)->find();
		return $this->get_image_by_path($now_goods['image'], $image_type);
	}
	
	/*根据商品数据表的图片字段来删除图片*/
	public function del_image_by_path($path)
	{
		if (!empty($path)) {
			unlink('.' . $path);
			return true;
		} else {
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function del_image_by_id($id)
	{
		$database_goods = D('Shop_goods');
		$condition_goods['goods_id'] = $id;
		$now_goods = $database_goods->field('`image`')->where($condition_goods)->find();
		return $this->del_image_by_path($now_goods['image']);
	}
}
?>