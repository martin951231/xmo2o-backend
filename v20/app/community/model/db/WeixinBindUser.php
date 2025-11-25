<?php


namespace app\community\model\db;

use think\Model;
class WeixinBindUser extends Model
{
    /**
     * 查询物业绑定公众号授权用户信息
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFind($where,$field=true,$orderby='id desc')
    {
        $info = $this->where($where)->field($field)->order($orderby)->find();
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }

    public function getList($where,$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }
}