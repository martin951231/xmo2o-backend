<?php
/* 
 * 得到商品的图片
 * 
 */
namespace app\group\model\service;

class GroupImageService
{
	/*根据商品数据表的图片字段的一段来得到图片*/
    public function getImageByPath($path,$image_type='-1'){
        if(!empty($path)){
            if (strpos($path,',')!==false) {
                //旧格式
                $image_tmp = explode(',',$path);
                if($image_type == '-1'){
                    $return['image'] = file_domain().'/upload/group/'.$image_tmp[0].'/'.$image_tmp['1'];
                    $return['m_image'] = file_domain().'/upload/group/'.$image_tmp[0].'/m_'.$image_tmp['1'];
                    $return['s_image'] = file_domain().'/upload/group/'.$image_tmp[0].'/s_'.$image_tmp['1'];
                }else{
                    $return = file_domain().'/upload/group/' . $image_tmp[0] . '/' . ($image_type ? $image_type . '_' : '') . $image_tmp['1'];
                }
            }else{
                //新版本
                if ($image_type == '-1') {
                    $return['image'] = file_domain() . $path;
                    $return['m_image'] = file_domain() . $path;
                    $return['s_image'] = file_domain() . $path;
                } else {
                    if ('http' === substr($path, 0, 4)) {
                        $return = $path;
                    } else if (strpos($path, '/upload') !== false) {
                        $return = file_domain() . $path;
                    } else {
                        $return = file_domain() . '/upload/group/' . $path;
                    }
                }
            }
            return $return;
        }else{
            return false;
        }
    }

	/*根据商品数据表的图片字段来得到图片*/
	public function getAllImageByPath($path,$image_type='-1'){
		if(!empty($path)){
			$tmp_pic_arr = explode(';',$path);
			foreach($tmp_pic_arr as $key=>$value){
				$image_tmp = explode(',',$value);
                $return[$key] = $this->getImageByPath($value,$image_type);
			}
			return $return;
		}else{
			return false;
		}
	}
}
?>