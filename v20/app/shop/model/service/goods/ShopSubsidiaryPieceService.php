<?php
/**
 * 外卖商品分类
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopSubsidiaryPiece as ShopSubsidiaryPieceModel;
class ShopSubsidiaryPieceService{
    public $shopSubsidiaryPieceModel = null;
    public function __construct()
    {
		$this->shopSubsidiaryPieceModel = new ShopSubsidiaryPieceModel();
	}
	
    /**
     * 根据商品获取附属菜列表
     * @param $goodsId int
     * @param $isCheck int 是否验证状态
     * @return array
     */
	public function getWapSubsidiaryPieceByGoodsId($goodsId,$isCheck=true)
    {
        if(!$goodsId){
            return [];
        }

        // 条件
        $where = [];
        $where[] = ['sp.goods_id', '=', $goodsId];
        $where[] = ['sp.is_delete', '=', 0];
        $where[] = ['sug.is_delete', '=', 0];
        if($isCheck){
            $where[] = ['g.status', '=', 1];
        }

        // 排序
        $order = [
            'id_sp' => 'ASC',
            'sug.sort' => 'DESC',
            'g.goods_id' => 'DESC',
        ];

        $subsidiaryPieceGoodslist = $this->getSubsidiaryPieceByCondition($where, $order);

        if(empty($subsidiaryPieceGoodslist)){
            return [];
        }
       
        // $subsidiary_piece_goodslist_goodids = array_column($subsidiary_piece_goodslist, 'goods_id');
        // $subsidiary_piece_goodslist_names = array_column(M(_view('Shop_goods'))->where(['goods_id'=>['in', $subsidiary_piece_goodslist_goodids]])->select(), 'name', 'goods_id');
        // $Shop_subsidiary_piece_ids = array_column($subsidiary_piece_goodslist, 'id_sp');
        // $Shop_subsidiary_piece_names = array_column(M(_view('Shop_subsidiary_piece'))->where(['id'=>['in', $Shop_subsidiary_piece_ids]])->select(), 'name', 'id');

        $list = array();
      
        foreach ($subsidiaryPieceGoodslist as $key => $value) {
            // $value['name_sp'] = isset($Shop_subsidiary_piece_names[$value['id_sp']]) ? $Shop_subsidiary_piece_names[$value['id_sp']] : $value['name_sp'];
            // $value['name'] = isset($subsidiary_piece_goodslist_names[$value['goods_id']]) ? $subsidiary_piece_goodslist_names[$value['goods_id']] : $value['name'];
            $value['name'] = htmlspecialchars_decode($value['name']);
            $value['name_sp'] = htmlspecialchars_decode($value['name_sp']);
            if($value['stock_num'] < 0 or $value['mininum_sug'] <= $value['stock_num']){
                $list[$value['id_sp']]['id_sp'] = $value['id_sp'];
                $list[$value['id_sp']]['name'] = $value['name_sp'];
                $list[$value['id_sp']]['maxnum'] = $value['maxnum_sp'];
                $list[$value['id_sp']]['mininum'] = $value['mininum_sp'];
                unset($value['name_sp']);
                unset($value['maxnum_sp']);
                unset($value['mininum_sp']);
                $list[$value['id_sp']]['goods'][] = $value;
            }
        }
        return array_values($list);
    }


    /**
     * 根据条件获取分类列表
     * @return array
     */
	public function getSubsidiaryPieceByCondition($where, $order = []){
		$list = $this->shopSubsidiaryPieceModel->getSubsidiaryPieceByCondition($where, $order);
		if(!$list) {
            return [];
        }
		return $list->toArray();
	}

}