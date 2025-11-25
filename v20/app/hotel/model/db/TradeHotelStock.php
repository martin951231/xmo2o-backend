<?php

/**
 * 酒店 
 * Author: 衡婷妹
 * Date Time: 2021/05/21
 */
namespace app\hotel\model\db;

use think\Model;
class TradeHotelStock extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

    public function getCatPrice($merId, $catId, $depTime, $endTime)
    {
		$where = $childWhere = [
			['mer_id', '=', $merId],
			['cat_id', '=', $catId],
			['stock_day', '>=', $depTime],
			['stock_day', '<', $endTime],
		];

        // 表前缀
        $result = $this->where($where) 
			->where('stock_id', 'IN', function ($query) use($childWhere){				
				$query->name('trade_hotel_stock')->where($childWhere)->field('max(stock_id)')->group('stock_day');
			})
            ->order(['stock_id'=>'asc'])
			->select();
        return $result;
    }
}