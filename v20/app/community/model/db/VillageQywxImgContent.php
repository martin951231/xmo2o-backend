<?php
/**
 * 企业微信图片库
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/19 14:36
 */

namespace app\community\model\db;

use think\Model;
class VillageQywxImgContent extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2021/3/19 14:43
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}