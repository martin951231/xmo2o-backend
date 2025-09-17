<?php
/**
 * 工作人员进度记录
 * @author weili
 * @datetime 2020/07/15 11:39
**/
namespace app\community\model\db;
use think\Model;
use think\facade\Db;
class HouseVillageRepairFollow extends Model
{
    /**
     * 获取所有工作人员进度记录
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @datetime 2020/07/15 11:40
     * @author weili
     */
    public function getAll($where,$field=true,$order='follow_id desc')
    {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/10/21 17:03
     */
    public function addData($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
}
