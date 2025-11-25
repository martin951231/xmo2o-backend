<?php
/**
 * 渠道活码绑定工作人员
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/22 19:42
 */
namespace app\community\model\db;

use think\Model;
class VillageQywxCodeBindWork extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    /**
     * Notes: 按条件删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2021/3/22 19:45
     */
    public function delWhere($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}