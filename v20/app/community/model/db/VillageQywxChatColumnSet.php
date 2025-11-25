<?php


namespace app\community\model\db;

use think\Model;
class VillageQywxChatColumnSet extends Model
{
    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }
    public function addFind($data)
    {
        $res = $this->insert($data);
        return $res;
    }
    public function editFind($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}
