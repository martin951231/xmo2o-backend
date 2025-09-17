<?php

/*
 * 得到餐饮店铺图片
 *
 */

namespace app\foodshop\model\service\store;
class FoodshopStoreImageService
{

    /* 根据商品数据表的图片字段的一段来得到图片 */
    public function getImageByPath($path, $imageType = '-1')
    {
        if (! empty($path)) {
            $imageTmp = explode(',', $path);
            if ($imageType == '-1') {
				if (strstr($imageTmp[0], '/upload/')) {
					$return['image'] = replace_file_domain($path);
				    $return['m_image'] = replace_file_domain($path);
				    $return['s_image'] = replace_file_domain($path);
				}else{
					$return['image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/' . $imageTmp['1']);
					$return['m_image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/m_' . $imageTmp['1']);
					$return['s_image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/s_' . $imageTmp['1']);
				}
            } else {
				if (strstr($imageTmp[0], '/upload/')) {
					$return = replace_file_domain($path);
				}else{
					$return = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/' . $imageType . '_' . $imageTmp['1']);
				}
            }
            return $return;
        } else {
            return false;
        }
    }

    /* 根据商品数据表的图片字段来得到图片 */
    public function getAllImageByPath($path, $imageType = '-1')
    {
        if (! empty($path)) {
            $tmpPicArr = explode(';', $path);
            foreach ($tmpPicArr as $key => $value) {
                $imageTmp = explode(',', $value);
                if ($imageType == '-1') {
                    $return[$key]['image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/' . $imageTmp['1']);
                    $return[$key]['m_image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/m_' . $imageTmp['1']);
                    $return[$key]['s_image'] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/s_' . $imageTmp['1']);
                } else {
                    $return[$key] = replace_file_domain('/upload/foodshopstore/' . $imageTmp[0] . '/' . $imageType . '_' . $imageTmp['1']);
                }
            }
            return $return;
        } else {
            return false;
        }
    }

    /* 根据商品数据表的图片字段来删除图片 */
    public function delImageByPath($path)
    {
        if (! empty($path)) {
            $imageTmp = explode(',', $path);
            unlink('./upload/foodshopstore/' . $imageTmp[0] . '/' . $imageTmp['1']);
            unlink('./upload/foodshopstore/' . $imageTmp[0] . '/m_' . $imageTmp['1']);
            unlink('./upload/foodshopstore/' . $imageTmp[0] . '/s_' . $imageTmp['1']);
            return true;
        } else {
            return false;
        }
    }
}
?>