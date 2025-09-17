<?php


namespace app\community\model\db;

use think\Model;
class WeixinBind extends Model
{
    /**
     * 查询物业绑定公众号信息
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }

}