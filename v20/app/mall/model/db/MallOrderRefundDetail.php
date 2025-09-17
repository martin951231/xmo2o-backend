<?php
namespace app\mall\model\db;

use think\Model;
use think\facade\Db;
class MallOrderRefundDetail extends Model{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getJoinData($where, $field = 'r.*,d.*'){
        $data = $this->alias('r')
                ->leftJoin('mall_order_detail d', 'r.order_detail_id=d.id')
                ->leftJoin('mall_goods mg', 'mg.goods_id=d.goods_id')
                ->field($field)
                ->where($where)
                ->select();
        return $data;

    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * @author 朱梦群
     * 根据order_detail_id 获取退款信息
     */
    public function getRefundByDetaiId($where, $field = 'r.*,d.*,mr.name as mer_name,ms.name as store_name')
    {
        $data = $this->alias('d')
            ->leftJoin('mall_order_refund r', 'd.refund_id=r.refund_id')
            ->leftJoin('mall_order or', 'or.order_id=d.order_id')
            ->leftJoin('merchant mr', 'mr.mer_id=or.mer_id')
            ->leftJoin('merchant_store ms', 'ms.store_id=or.store_id')
            ->field($field)
            ->where($where)
            ->select();
        if (!empty($data)) {
            return $data->toArray();
        } else {
            return [];
        }
    }
}