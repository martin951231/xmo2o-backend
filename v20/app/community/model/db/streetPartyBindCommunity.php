<?php


namespace app\community\model\db;

use think\Model;
class streetPartyBindCommunity extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/9/15 10:09
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}