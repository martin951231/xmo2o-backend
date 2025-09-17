<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeActivityGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取活动商品列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 0, $field = 'a.*,g.price,g.small_price,g.image,g.employee_lables,g.name as goods_name,s.name as store_name,m.name as mer_name,g.spec_value', $order = 'a.sort desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('a')
                ->field($field)
                ->join($prefix . 'shop_goods g', 'g.goods_id = a.goods_id', 'left')
                ->join($prefix . 'merchant_store s', 's.store_id = a.store_id', 'left')
                ->join($prefix . 'merchant m', 'm.mer_id = a.mer_id', 'left')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $data = $this->alias('a')
                ->field($field)
                ->join($prefix . 'shop_goods g', 'g.goods_id = a.goods_id', 'left')
                ->join($prefix . 'merchant_store s', 's.store_id = a.store_id', 'left')
                ->join($prefix . 'merchant m', 'm.mer_id = a.mer_id', 'left')
                ->where($where)
                ->limit($limit)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        } else {
            $data = $this->alias('a')
                ->field($field)
                ->join($prefix . 'shop_goods g', 'g.goods_id = a.goods_id', 'left')
                ->join($prefix . 'merchant_store s', 's.store_id = a.store_id', 'left')
                ->join($prefix . 'merchant m', 'm.mer_id = a.mer_id', 'left')
                ->where($where)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        }
        return $arr;
    }

    /**
     * 获取全部活动店铺
     * @param $where array
     * @return array
     */
    public function getStoreList($where = [], $field = 's.store_id as value,s.name as title,s.cat_id,s.cat_fid,c.cat_name,cf.cat_name as cat_fname', $order = 'cf.cat_sort desc') {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->field($field)
            ->join($prefix . 'shop_goods g', 'g.goods_id = a.goods_id', 'left')
            ->join($prefix . 'merchant_store s', 's.store_id = a.store_id', 'left')
            ->join($prefix . 'merchant_category c', 's.cat_id = c.cat_id', 'left')
            ->join($prefix . 'merchant_category cf', 's.cat_fid = cf.cat_id', 'left')
            ->join($prefix . 'merchant m', 'm.mer_id = a.mer_id', 'left')
            ->where($where)
            ->order($order)
            ->group('s.store_id')
            ->select();
        return !empty($arr) ? $arr->toArray() : [];
    }

}