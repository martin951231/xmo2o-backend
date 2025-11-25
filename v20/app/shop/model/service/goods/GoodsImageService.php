<?php
/**
 * 得到外卖商品的图片
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 13:42
 */
namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoods as ShopGoodsModel;
class GoodsImageService
{
	/*根据商品数据表的图片字段的一段来得到图片*/
	public function getImageByPath($path, $image_type='-1')
	{
		if (!empty($path)) {
            $filepath = 'goods';
            if (strstr($path, 'sysgoods_')) {
                $filepath = 'sysgoods';
            }
			if ($image_type == '-1') {
			    if (strpos($path,',')!==false) {
					$temp_path = explode(',',$path);
					if (strpos($path,'/upload/' . $filepath. '/')!==false){
						$return['image'] = replace_file_domain($temp_path[0].'/'.$temp_path[1]);
						$return['m_image'] = replace_file_domain($temp_path[0].'/m_'.$temp_path[1]);
						$return['s_image'] = replace_file_domain($temp_path[0].'/s_'.$temp_path[1]);
					}else{		
						$return['image'] = replace_file_domain('/upload/' . $filepath. '/'.$temp_path[0].'/'.$temp_path[1]);
						$return['m_image'] = replace_file_domain('/upload/' . $filepath. '/'.$temp_path[0].'/m_'.$temp_path[1]);
						$return['s_image'] = replace_file_domain('/upload/' . $filepath. '/'.$temp_path[0].'/s_'.$temp_path[1]);
					}	
				}else{
					$return['image'] = replace_file_domain($path);
					$return['m_image'] = replace_file_domain($path);
					$return['s_image'] = replace_file_domain($path);
				}
			} else {
				if('http' === substr($path,0,4)){
					$return = $path;
				}else{
					if (strpos($path,',')!==false) {
						if (strpos($path,'/upload/' . $filepath. '/')!==false){
							$path = explode(',',$path);
							$return = replace_file_domain($path[0].'/'.$path[1]);
						}else{		
							$path = explode(',',$path);
							$return = replace_file_domain('/upload/' . $filepath. '/'.$path[0].'/'.$path[1]);
						}
					}else{
						$return = replace_file_domain($path);
					}
				}
			}
			return $return;
		} else if(cfg('goods_default_image') && cfg('is_open_goods_default_image')){
			return cfg('goods_default_image');
        } else if (cfg('GOODS_DEFAULT_IMAGE')) {
            return cfg('GOODS_DEFAULT_IMAGE');
        }else {
			return cfg('site_url').'/tpl/Merchant/default/static/images/default_img.png';;
		}
	}

	/*根据商品数据表的图片字段来得到图片*/
	public function getAllImageByPath($path, $image_type='-1')
	{
		if (!empty($path)) {
			$tmp_pic_arr = explode(';', $path);
			foreach ($tmp_pic_arr as $key => $value) {
				$return[$key] = $this->getImageByPath($value,$image_type);
			}
			return $return;
		} else if(cfg('goods_default_image') && cfg('is_open_goods_default_image')){
			if ($image_type == '-1') {
				$return[0]['image'] = cfg('goods_default_image');
				$return[0]['m_image'] = cfg('goods_default_image');
				$return[0]['s_image'] = cfg('goods_default_image');
			} else {
				$return[0] = cfg('goods_default_image');
			}
			return $return;
		} else {
            if ($image_type == '-1') {
                $return[0]['image'] = cfg('goods_default_image');
                $return[0]['m_image'] = cfg('goods_default_image');
                $return[0]['s_image'] = cfg('goods_default_image');
            } else {
                $return[0] = cfg('site_url') . '/tpl/Merchant/default/static/images/default_img.png';
            }
			return $return;
		}
	}

    /**
     * 获得一张图片
     * @return string
     */
    public function getOneImage($tmpPicArr=[]){
        $product_image = $tmpPicArr ? $tmpPicArr[0] : '';
        if(!$product_image){
            if (cfg('is_open_goods_default_image')&&cfg('goods_default_image')) {
                $product_image = cfg('goods_default_image');
            }else{
                $product_image = cfg('site_url').'/tpl/Merchant/default/static/images/default_img.png';
            }
        }
        return $product_image;
    }

	/*根据商品数据表的自增ID字段来得到图片*/
	public function getImageById($id, $imageType)
	{	
		$shopGoodsModel = new ShopGoodsModel();
		$nowGoods = $shopGoodsModel->getGoodsByGoodsId($id);
		return $this->get_image_by_path($nowGoods['image'], $imageType);
	}
	
	/*根据商品数据表的图片字段来删除图片*/
	public function delImageByPath($path)
	{
		if (!empty($path)) {
			unlink('.' . $path);
			return true;
		} else {
			return false;
		}
	}

	/*根据商品数据表的自增ID字段来得到图片*/
	public function delImageById($id)
	{
		$shopGoodsModel = new ShopGoodsModel();
		$nowGoods = $shopGoodsModel->getGoodsByGoodsId($id);
		return $this->delImageByPath($nowGoods['image']);
	}
}
?>