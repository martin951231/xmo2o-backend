<?php
/**
 * ImageDecorate.php
 * 自定义装修图片model
 * Create on 2021/2/22 11:38
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class ImageDecorate extends Model
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