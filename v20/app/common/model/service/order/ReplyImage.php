<?php
/* 
 * 得到评论的图片
 * 
 */

namespace app\common\model\service\order;
use app\common\model\service\order\ReplyPicService;

class ReplyImage{
	/*
	 *	根据评论图片表的pic字段返回图片信息
	 *  param @path 路径 string
	 *  param @type 图片的类别（团购或订餐） string 
	 *  param @image_type 大小图片（m或s）或不传参 number
	 *  return void 有image_type返回 string，没有image_type返回 array
	 */
	
	public function getImageByPath($path,$type,$imageType='-1'){
		if(!empty($path)){
			$image_tmp = explode(',',$path);
			if($imageType == '-1'){
                if (strpos($path, '/upload') !== false) {
                    if(empty($image_tmp[1])){
                        $img = count($image_tmp) > 1 ? $image_tmp[0]: $path;
                    }else{
                        $img = count($image_tmp) > 1 ? $image_tmp[0] . '/' . $image_tmp[1] : $path;
                    }
                    $return['image'] = $return['m_image'] = $return['s_image'] = replace_file_domain($img);
                } else {
                    $return['image'] = file_domain() . '/upload/reply/' . $type . '/' . $image_tmp[0] . '/' . $image_tmp[1];
                    $return['m_image'] = file_domain() . '/upload/reply/' . $type . '/' . $image_tmp[0] . '/m_' . $image_tmp[1];
                    $return['s_image'] = file_domain() . '/upload/reply/' . $type . '/' . $image_tmp[0] . '/s_' . $image_tmp[1];
                    if(empty($image_tmp[1])) {
                        $return['image'] = file_domain() . $image_tmp[0];
                        $return['m_image'] =file_domain() . $image_tmp[0];
                        $return['s_image'] =file_domain() . $image_tmp[0];
                    }

                }
			}else{
                if (strpos($path, '/upload') !== false) {
                    $img = count($image_tmp) > 1 ? $image_tmp[0] . '/' . $image_tmp[1] : $path;
                    $return = replace_file_domain($img);
                } else {
                    $return = file_domain() . '/upload/reply/' . $type . '/' . $image_tmp[0] . '/' . $imageType . '_' . $image_tmp[1];
                }
			}
			return $return;
		}else{
			return false;
		}
	}
	/*根据商品数据表的图片字段来得到图片*/
	public function getAllImageByPath($path,$imageType='-1'){
		if(!empty($path)){
			$tmp_pic_arr = explode(';',$path);
			foreach($tmp_pic_arr as $key=>$value){
				$image_tmp = explode(',',$value);
				if($imageType == '-1'){
					$return[$key]['image'] = file_domain().'/upload/group/'.$image_tmp[0].'/'.$image_tmp[1];
					$return[$key]['m_image'] = file_domain().'/upload/group/'.$image_tmp[0].'/m_'.$image_tmp[1];
					$return[$key]['s_image'] = file_domain().'/upload/group/'.$image_tmp[0].'/s_'.$image_tmp[1];
				}else{
					$return[$key] = file_domain().'/upload/group/'.$image_tmp[0].'/'.$imageType.'_'.$image_tmp[1];
				}
			}
			return $return;
		}else{
			return false;
		}
	}
	/*根据评论图片数据表的 order_id order_type 字段来得到图片*/
	public function getImageById($orderId,$orderType){
		$condition['order_id'] = $orderId;
		$condition['order_type'] = $orderId;
		$picList = (new ReplyPicService())->getSome($condition,'pic',['pigcms_id'=>'ASC']);
		
		if($orderType == 0){
			$filPath = 'group';
		}else{
			$filPath = 'meal';
		}
		$newPicList = array();
		foreach($picList as $key=>$value){
			array_push($newPicList,$this->getImageByPath($value['pic'],$filPath));
		}
		return $newPicList;
	}
	
	/*根据评论图片数据表的 一些order_id order_type 字段来得到图片*/
	public function getImageByIds($pigcms_ids,$orderType){
		$condition[] = ['pigcms_id','in', $pigcms_ids];
		$picList = (new ReplyPicService())->getSome($condition,'pic',['pigcms_id'=>'ASC']);
		
		if($orderType == 0){
			$filPath = 'group';
		}elseif($orderType == 1){
			$filPath = 'meal';
		}else{
			$filPath = 'appoint';
		}
		$new_picList = array();
		foreach($picList as $key=>$value){
			array_push($new_picList,$this->getImageByPath($value['pic'],$filPath));
		}
		return $new_picList;
	}
}
?>