<?php
/**
 * 景区推荐美食酒店等列表
 */

namespace app\life_tools\model\db;

use think\Model;

class LifeToolsScenicRecommend extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     */
    public function getList($where = [], $field = '*', $order = 'sort desc', $limit = 20)
    {
        if (is_array($limit)) {
            $list = $this->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $arr = $this->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
            $list = [
                'data' => []
            ];
            if (!empty($arr)) {
                $list['data'] = $arr->toArray();
            }
        }
        return $list;
    }

}