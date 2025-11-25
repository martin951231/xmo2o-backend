<?php
/* 
 * 团购酒店模块的图片
 * 
 */
namespace app\hotel\model\service;

class TradeHotelImageService
{
	/*根据商品数据表的图片字段的一段来得到图片*/
	public function getImageByPath($path, $image_type='-1')
	{
		if (!empty($path)) {
			$image_tmp = explode(',', $path);
			if ($image_type == '-1') {
				$return['image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/' . $image_tmp['1'];
				$return['m_image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
				$return['s_image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
			} else {
				$return = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
			}
			return $return;
		} else {
			return false;
		}
	}
	/*根据商品数据表的图片字段来得到图片*/
	public function getAllImageByPath($path, $image_type='-1')
	{
		if (!empty($path)) {
			$tmp_pic_arr = explode(';', $path);
			foreach ($tmp_pic_arr as $key => $value) {
				$image_tmp = explode(',', $value);
				if ($image_type == '-1') {
					$return[$key]['image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/' . $image_tmp['1'];
					$return[$key]['m_image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/m_' . $image_tmp['1'];
					$return[$key]['s_image'] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/s_' . $image_tmp['1'];
				} else {
					$return[$key] = file_domain() . '/upload/trade_hotel/' . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
				}
			}
			return $return;
		} else {
			return false;
		}
	}
}
?>