<?php
/* 
 * 得到商品的图片
 * 
 */
class waimai_image{
	// 获取优惠图标
	static public function get_icon_by_path($path){
		if(!empty($path)){
			$return = C('config.site_url').'/upload/waimai/'.$path;
			return $return;
		}else{
			return false;
		}
	}
	
	/*根据商品数据表的图片字段的一段来得到图片*/
	static public function get_image_by_path($path,$image_type='-1'){
		if(!empty($path)){
			$image_tmp = explode(',',$path);
			if($image_type == '-1'){
				$return['image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/'.$image_tmp['1'];
				$return['m_image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/m_'.$image_tmp['1'];
				$return['s_image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/s_'.$image_tmp['1'];
			}else{
				$return = file_domain().'/upload/waimai/'.$image_tmp[0].'/'.$image_type.'_'.$image_tmp['1'];
			}
			return $return;
		}else{
			return false;
		}
	}
	
	/*根据商品数据表的图片字段来得到图片*/
	public function get_allImage_by_path($path,$image_type='-1'){
		if(!empty($path)){
			$tmp_pic_arr = explode(';',$path);
			foreach($tmp_pic_arr as $key=>$value){
				$image_tmp = explode(',',$value);
				if($image_type == '-1'){
					$return[$key]['image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/'.$image_tmp['1'];
					$return[$key]['m_image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/m_'.$image_tmp['1'];
					$return[$key]['s_image'] = file_domain().'/upload/waimai/'.$image_tmp[0].'/s_'.$image_tmp['1'];
				}else{
					$return[$key] = file_domain().'/upload/waimai/'.$image_tmp[0].'/'.$image_type.'_'.$image_tmp['1'];
				}
			}
			return $return;
		}else{
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function get_image_by_id($id,$image_type){
		$database_meal = D('Waimai_goods');
		$condition_meal['goods_id'] = $id;
		$now_meal = $database_meal->field('`image`')->where($condition_meal)->find();
		return $this->get_image_by_path($now_meal['image'],$image_type);
	}
	
	/*根据商品数据表的图片字段来删除图片*/
	public function del_image_by_path($path){
		if(!empty($path)){
			$image_tmp = explode(',',$path);
			unlink('./upload/waimai/'.$image_tmp[0].'/'.$image_tmp['1']);
			unlink('./upload/waimai/'.$image_tmp[0].'/m_'.$image_tmp['1']);
			unlink('./upload/waimai/'.$image_tmp[0].'/s_'.$image_tmp['1']);
			return true;
		}else{
			return false;
		}
	}
	/*根据商品数据表的自增ID字段来得到图片*/
	public function del_image_by_id($id){
		$database_meal = D('Waimai_goods');
		$condition_meal['goods_id'] = $id;
		$now_meal = $database_meal->field('`image`')->where($condition_meal)->find();
		return $this->del_image_by_path($now_meal['image']);
	}
}
?>