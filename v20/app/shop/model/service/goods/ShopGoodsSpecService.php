<?php
/**
 * 外卖商品规格
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoodsSpec as ShopGoodsSpecModel;
class ShopGoodsSpecService{
    public $shopGoodsSpecModel = null;
    public function __construct()
    {
		$this->shopGoodsSpecModel = new ShopGoodsSpecModel();
	}

    /**
     * 根据商品id获取规格列表
     * @param $goodsId
     * @return array
     */
	public function getSpecByGoodsId($goodsId){
		$list = $this->shopGoodsSpecModel->getSpecByGoodsId($goodsId);
		if(!$list) {
            return [];
        }
		return $list->toArray();
	}
}