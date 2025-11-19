<?php
/**
 * 外卖商品分类
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopSubsidiaryPiece as ShopSubsidiaryPieceModel;
class ShopSubsidiaryPieceGoodsService{
    public $shopSubsidiaryPieceModel = null;
    public function __construct()
    {
		$this->shopSubsidiaryPieceModel = new ShopSubsidiaryPieceModel();
	}
	
    /**
     * 根据商品获取附属菜列表
     * @param $where
     * @return array
     */
	public function getSubsidiaryPieceGoods($where)
    {
        if(!$where){
            return [];
        }

        $where['sug.is_delete'] = "0";

        $subsidiaryPieceGoods = $this->shopSubsidiaryPieceModel->getSubsidiaryPieceByCondition($where);

        if(empty($subsidiaryPieceGoods)){
            return [];
        }
      
        return $subsidiaryPieceGoods->toArray();
    }

}