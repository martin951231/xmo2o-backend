<?php
/**
 * 汪晨
 * 2021/08/24
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingOrderTypeStore extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //获取店铺列表
    public function getList($where = [], $field = true, $order = [], $page = 0, $limit = 0)
    {
        if ($limit == 0) {
            $result = $this->where($where)->field($field)->order($order)->select();
        } else {
            $result = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        }
        if (empty($result)) {
            return [];
        } else {
            return $result->toArray();
        }
    }

}