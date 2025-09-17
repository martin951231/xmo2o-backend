<?php


namespace app\community\model\db;
use think\Model;

class HouseEConfig extends Model
{
    /**
     * 获取小区电子发票配置
     * @author lijie
     * @date_time 2020/07/07
     * @param string $where
     * @param string $field
     * @return array|mixed|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfig($where,$field='*')
    {
        $data = $this->where($where)->find();
        return $data;
    }
}