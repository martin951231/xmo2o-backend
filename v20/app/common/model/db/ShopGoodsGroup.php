<?php
/**
 * ShopGoodsGroup.php
 * 文件描述
 * Create on 2021/3/11 14:59
 * Created by zhumengqun
 */
namespace app\common\model\db;

use think\Model;

class ShopGoodsGroup extends Model
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