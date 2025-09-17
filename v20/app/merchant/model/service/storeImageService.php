<?php
/**
 * 得到店铺的图片
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/27 13:57
 */
namespace app\merchant\model\service;
class storeImageService{
	/**
     * 根据店铺数据表的图片字段的一段来得到图片
     * @param $path string 图片路径
     * @return string
     */
    public function getImageByPath($path)
    {
        if (!empty($path)) {
            $imageTmp = explode(',', $path);
            if (count($imageTmp) == 2) {
                $return = replace_file_domain('/upload/store/' . $imageTmp[0] . '/' . $imageTmp['1']);
            } else {
                $return = replace_file_domain($path);
            }
            return $return;
        } else {
            return '';
        }
    }
	
	/**
     * 根据店铺数据表的图片字段来得到图片
     * @param $path string 图片路径
     * @return array|bool
     */
	public function getAllImageByPath($path){
		if(!empty($path)){
			$tmpPicArr = explode(';',$path);
			foreach($tmpPicArr as $key=>$value){
			    if('http' === substr($value,0,4)){
					$return[$key] = $value;
				}elseif(stripos($value, ',')){
					$tmp_pic = explode(',', $value);
					$return[$key] = replace_file_domain('/upload/store/'.$tmp_pic[0].'/'.$tmp_pic[1]);
				}else{
                    $return[$key] = replace_file_domain($value);
                }
			}
			return $return;
		}else{
			return [];
		}
	}

	/**
     * 根据店铺数据表的图片字段来删除图片
     * @param $path string 图片路径
     * @return bool
     */
	public function delImageByPath($path){
		if(!empty($path)){
			unlink('.'.$path);
			return true;
		}else{
			return false;
		}
	}
}
?>