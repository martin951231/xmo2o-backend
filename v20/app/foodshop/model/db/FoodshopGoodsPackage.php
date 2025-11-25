<?php
/**
 * 店铺套餐表model
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2020年12月14日17:17:14
 */


namespace app\foodshop\model\db;

use think\Model;

class FoodshopGoodsPackage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function del($where)
    {
        return $this->where($where)->delete();
    }

    /**根据条件返回套餐总数
     * @param $where    array 条件
     * @return mixed
     */
    public function getPackageCountByJoin($where)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $count = $this->alias('p')
            ->where($where)
            ->join($prefix . 'foodshop_goods_package_detail d', 'p.id = d.pid', 'left')
            ->count();
        return $count;
    }


    /**根据条件返回套餐列表
     * @param $where    array 条件
     * @param $field    string 字段
     * @return mixed
     */
    public function getPackageListByJoin($where, $field)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->where($where)
            ->field($field)
            ->join($prefix . 'foodshop_goods_package_detail d', 'p.id = d.pid', 'left')
            ->select();
        if ($result) {
            return  $result->toArray();
        } else {
            return [];
        }
    }


    public function getPackageList($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $this->name = _view($this->name);
        $res = $this->getProSome($where, $field, $order, $page, $limit);

        return $res;
    }

    public function getInfoByID($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $this->name = _view($this->name);
        $res = $this->getOne($where, $field, $order, $page, $limit);

        return $res;
    }
}