<?php
/**
 * 外卖商品规格对应属性值
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoodsSpecValue as ShopGoodsSpecValueModel;
class ShopGoodsSpecValueService{
    public $shopGoodsSpecValueModel = null;
    public function __construct()
    {
		$this->shopGoodsSpecValueModel = new ShopGoodsSpecValueModel();
	}

    /**
     * 获取多条数据
     * @param $where
     * @return array
     */
    public function getSome($where,$field = true,$order=true,$page=0,$limit=0){
        $list = $this->shopGoodsSpecValueModel->getSome($where,$field,$order,$page,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 根据规格id获取规格值
     * @param $sid
     * @return array
     */
	public function getSpecValueBySid($sid){
		$list = $this->shopGoodsSpecValueModel->getSpecValueBySid($sid);
		if(!$list) {
            return [];
        }
		return $list->toArray();
	}
}