<?php
/**
 * 健身推荐课程model
 */

namespace app\life_tools\model\db;

use think\Model;

class LifeToolsRecommendCourse extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getDetail($where, $page, $pageSize, $order = 'id DESC')
    {
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('r')
            ->field('g.*,r.status,r.create_time,r.sort')
            ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
            ->where($where)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $arr;
    }

    /**
     * @param $where
     * @return array
     * 联合查询获取关联商品的详细
     */
    public function getList($where, $filed = 'g.*,r.status,r.create_time,r.sort', $order = 'r.sort DESC', $limit = 3)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($filed)
            ->join($prefix . 'life_tools g', 'r.tools_id = g.tools_id')
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

}