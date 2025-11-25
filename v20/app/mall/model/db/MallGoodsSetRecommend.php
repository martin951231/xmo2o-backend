<?php


namespace app\mall\model\db;


use think\Model;

class MallGoodsSetRecommend extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param array $where
     * @param bool $field
     * @param array $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询
     */
    public function getOne($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param array $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delData($where = []) {
        $result = $this->where($where)->delete();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}