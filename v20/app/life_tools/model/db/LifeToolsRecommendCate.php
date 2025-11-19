<?php
/**
 * 景区商品推荐分类model
 */
namespace app\life_tools\model\db;

use think\Model;

class LifeToolsRecommendCate extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail($where, $page, $pageSize, $order = 'r.sort DESC')
    {
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('r')
            ->field('g.*,r.status,r.create_time,r.sort')
            ->join($prefix . 'merchant_category g', 'r.cat_id = g.cat_id')
            ->where($where)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $arr;
    }

}