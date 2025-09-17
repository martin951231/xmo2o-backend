<?php
/**
 * MicroPageDecorateField.php
 * 微页面装修内容
 * Create on 2021/2/20 15:56
 * Created by zhumengqun
 */
namespace app\common\model\db;

use think\Model;

class MicroPageDecorateField extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 根据条件删除
     */
    public function delOne($where)
    {
        return $this->where($where)->delete();
    }
}