<?php
/**
 * 登录参数标记记录相关信息
 */
namespace app\community\model\db\workweixin;

use think\Model;

class WorkWeixinLoginSign extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteInfo($where=array()){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }
}