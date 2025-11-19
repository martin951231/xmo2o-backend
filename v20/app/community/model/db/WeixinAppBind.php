<?php


namespace app\community\model\db;

use think\Model;
class WeixinAppBind extends Model
{
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

}