<?php
/**
 * NavBottomDecorate.php
 * 底部导航自定义装修
 * Create on 2021/2/20 9:37
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class NavBottomDecorate extends Model
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