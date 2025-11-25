<?php
/**
 * 电子面单打印记录表
 * Create on 2020年11月27日14:21:45
 * Created by 钱大双
 */

namespace app\common\model\db;

use think\Model;

class ElectronicSheetPrint extends Model
{
    /**添加数据 获取插入的数据id
     * @param $data
     * @return int|string
     */
    public function addPrint($data)
    {
        return $this->insertGetId($data);
    }

    /**修改数据
     * @param $data
     * @param $where
     * @return ElectronicSheetPrint
     */
    public function updatePrint($data, $where)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }


    /**查询一条记录
     * @param $where
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where, $order = 'create_time desc')
    {
        $result = $this->where($where)->order($order)->find();
        if (!empty($result)) {
            $result = $result->toArray();
        }
        return $result;
    }
}