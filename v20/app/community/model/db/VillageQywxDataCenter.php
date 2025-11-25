<?php

namespace app\community\model\db;

use think\Model;

class VillageQywxDataCenter extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 数据统计
     * @author lijie
     * @date_time 2021/03/25
     * @param $where
     * @param bool $field
     * @return float
     */
    public function getSum($where,$field=true)
    {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    /**
     * 添加数据
     * @author lijie
     * @date_time 2021/03/25
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 获取列表
     * @author lijie
     * @date_time 2021/03/25
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        return $data;
    }
}