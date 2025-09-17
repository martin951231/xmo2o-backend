<?php


namespace app\mall\model\db;


use think\Model;

class MallGoodsArea extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 硬删除
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delData($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param string $field
     * @param array $where
     * @return array
     * 获取字段值
     */
    public function getColumn($field = 'id', $where = []) {
        $result = $this->where($where)->column($field);
        return $result;
    }
}