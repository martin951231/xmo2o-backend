<?php


namespace app\merchant\model\db;

use think\Model;
class GroupRecommend extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where){
        $result = $this ->alias('m')
            ->join('group g','m.group_id = g.group_id')
            ->field('g.*')
            ->where($where)
            ->select()
            ->toArray();
        return $result;
    }
}