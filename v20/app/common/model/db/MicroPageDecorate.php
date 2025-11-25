<?php
/**
 * MicroPageDecorate.php
 * 微页面装修
 * Create on 2021/2/20 15:55
 * Created by zhumengqun
 */

namespace app\common\model\db;

use think\Model;

class MicroPageDecorate extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $json = ['bg_image'];
    public $jsonAssoc = true;
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

    public function setInc($where,$field){
        return $this->where($where)->setInc($field,1);
    }
}