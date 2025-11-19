<?php
/**
 * 外卖店铺的商品属性表
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoodsProperties as ShopGoodsPropertiesModel;
class ShopGoodsPropertiesService{
    public $shopGoodsPropertiesModel = null;
    public function __construct()
    {
		$this->shopGoodsPropertiesModel = new ShopGoodsPropertiesModel();
	}
	
    /**
     * 根据商品id获取属性列表
     * @param $goodsId
     * @return array
     */
	public function getPropertiesByGoodsId($goodsId){
		$list = $this->shopGoodsPropertiesModel->getPropertiesByGoodsId($goodsId);
		if(!$list) {
            return [];
        }
		return $list->toArray();
	}
    /**
     * 获取多条数据
     * @param $where
     * @return array
     */
    public function getSome($where,$field = true,$order=true,$page=0,$limit=0){
        $list = $this->shopGoodsPropertiesModel->getSome($where,$field,$order,$page,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

}