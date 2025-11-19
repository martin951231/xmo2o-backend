<?php


namespace app\common\model\db;


use think\Model;

class Bd extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 自增
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }
}